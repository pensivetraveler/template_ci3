<?php
defined('BASEPATH') OR exit('No direct script access 1wed');

trait BuilderColumnsTrait
{
    /**
     * 현재 요청(클래스·메서드)의 엔티티 설정 후보 키 목록. 규칙은 Resource_loader::entityConfigNames()
     */
    protected function setConfigList($flag, $add = null)
    {
        return $this->resourceLoader->entityConfigNames($flag, $this->router->class, $this->router->method, $add);
    }

    protected function setFormColumns($configData = null): array
    {
        $config = [];
        if(is_array($configData) && count($configData)) {
            $config = $configData;
        }elseif(is_string($configData) && strlen($configData)){
            $config = $this->resourceLoader->entityConfig($this->resourceLoader->entityConfigName('form', $configData), [], true);
        }

        if(empty($config)){
            $method = $this->router->method;
            if($this->router->method === 'index' && $this->routeConfig['properties']['baseMethod']) {
                $method = $this->routeConfig['properties']['baseMethod'];
            }
            $config = $this->resourceLoader->entityConfig($this->setConfigList(
                'form', $method ?? null
            ), [], false);

            if(empty($config)) {
                $this->logging(__METHOD__." : config does not exist.", E_USER_WARNING, false);
                return $config;
            }
        }

        return array_reduce($config, function($carry, $item) {
            if(isset($item['field']) || $item['type'] === 'common') {
                $carry[] = $this->setFormColumn($item);
            }
            return $carry;
        }, []);
    }

    /**
     * list 설정이 없을 때 form 설정의 list => true 항목으로 목록 항목을 만든다 (WEB·API 공통).
     * 목록 전용 값은 list_attributes (항목 값보다 우선, MY_Builder_WEB::setListColumns)
     */
    protected function getListConfigFromForm(array $formConfig): array
    {
        $list = [];
        foreach ($formConfig as $item) {
            if(empty($item['list']) || !isset($item['field']) || ($item['type'] ?? '') === 'common') continue;
            $attributes = is_array($item['list_attributes'] ?? null) ? $item['list_attributes'] : [];
            $list[] = array_merge($attributes, [
                'field' => $item['field'],
                'label' => is_empty($attributes, 'label') ? ($item['label'] ?? '') : $attributes['label'],
                'option_attributes' => !empty($attributes['option_attributes']) ? $attributes['option_attributes'] : ($item['option_attributes'] ?? []),
                'list_attributes' => $attributes,
            ]);
        }
        return $list;
    }

    protected function setFormColumn($item)
    {
        $item = array_merge(
            $this->config->get("builder_form_base", []),
            $item
        );

        if(isset($item['type']) && $item['type'] === 'common') return $item;

        // builder_form_base 의 label 기본값('')이 병합되므로 빈 값도 자동 라벨로 처리
        if(!is_string($item['label'] ?? null) || $item['label'] === '') {
            $item['label'] = 'lang:'.$this->router->class.'.'.$item['field'];
        }

        if(sscanf($item['label'], 'lang:%s', $line) === 1)
            $item['label'] = $line;

        $item = $this->setColumnErrors($item);

        if($item['type'] === $item['subtype']) $item['subtype'] = 'base';

        // list attributes
        $item['list_attributes'] = array_merge(
            $this->config->get("builder_list_base", []),
            $item['list_attributes']
        );

        // option attributes
        if(isset($item['option_attributes']) && count($item['option_attributes'])) {
            $item['option_attributes'] = array_merge(
                $this->config->get("builder_form_base_option_attributes", []),
                $item['option_attributes']
            );
            $item['options'] = $this->getOptions($item['option_attributes']['option_field'] ?? $item['field'], $item['option_attributes']);
            // option_data.select.extra(또는 render.extra) 값 → <option data-*> (get_admin_form_attributes 가 data-option-extra 로)
            if(count($this->lastOptionExtras)) $item['option_extras'] = $this->lastOptionExtras;
        }

        // form attributes
        $item['form_attributes'] = array_merge(
            $this->config->get("builder_form_base_form_attributes", []),
            $item['form_attributes']
        );

        // file
        if($item['type'] === 'file') {
            $upload_config = $this->config->item($this->router->class . '_' . $item['field'] . '_upload_config')
                ?: $this->config->item($item['field'] . '_upload_config')
                    ?: $this->config->item('base_upload_config');

            if(!isset($upload_config['allowed_types'])) $upload_config['allowed_types'] = '';

            $item['attributes'] = array_merge([
                'max_size' => get_file_size_to_kb($upload_config['max_size']),
                'accept' => convert_allowed_types_to_accept($upload_config['allowed_types']),
            ], $item['attributes']);
        }

        return $item;
    }

    protected function setColumnErrors($item)
    {
        $rules = preg_split('/\|(?![^\[]*\])/', $item['rules']);

        if($matches = preg_grep('/^required$/', $rules)) {
            $item['attributes']['required'] = $matches[1]??$matches[0];
        }

        if($matches = preg_grep('/^required_mod\[(.*?)\]$/', $rules)) {
            $option = reset($matches);
            if (preg_match('/^required_mod\[(.*?)\]$/', $option, $matches)) {
                $item['attributes']['required-mod'] = $matches[1];
                if(in_array($this->router->method, explode('|', $matches[1]))){
                    $item['rules'] = str_replace($matches[0], 'required', $item['rules']);
                    $item['attributes']['required'] = 'required';
                }
            }
        }

        // 전처리 이후 에러 메세지 셋업
        $rules = preg_split('/\|(?![^\[]*\])/', $item['rules']);

        $item['errors'] = array_reduce($rules, function($carry, $rule) use ($item) {
            $param = null;
            if(count(preg_split('/\[/', $rule)) > 1) {
                preg_match('/(.*?)\[(.*)\]/', $rule, $match);
                $rule = $match[1];
                $param = $match[2];
            }
            if($error_msg = $this->form_validation->get_error_msg($rule, $item['label'], $param, $item['errors'])){
                $carry[$rule] = $error_msg;
            }
            return $carry;
        }, []);

        return $item;
    }

}
