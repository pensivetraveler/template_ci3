<?php
defined('BASEPATH') OR exit('No direct script access allowed');

trait BuilderColumnsTrait
{
    protected function setFormColumns($configData = null): array
    {
        $config = [];
        if(is_array($configData) && count($configData)) {
            $config = $configData;
        }elseif(is_string($configData) && strlen($configData)){
            $config = $this->config->get('form_'.$configData.'_config', []);
        }

        if(empty($config)){
            $method = $this->router->method;
            if($this->router->method === 'index' && $this->routeConfig['properties']['baseMethod']) {
                $method = $this->routeConfig['properties']['baseMethod'];
            }
            $config = $this->config->get2(
                'form_'.snakeize($this->router->class).'_config'
                , 'form_'.$method.'_config'
                , [], false);

            if(empty($config)) {
                $this->logger("setFormColumns : config does not exist.", E_USER_WARNING, false);
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

    protected function setFormColumn($item)
    {
        if(isset($item['type']) && $item['type'] === 'common') return $item;

        $item = array_merge(
            $this->config->get("builder_form_base", []),
            ['label' => 'lang:'.$this->router->class.'.'.$item['field']],
            $item
        );

        if(sscanf($item['label'], 'lang:%s', $line) === 1)
            $item['label'] = $line;

        $item = $this->setColumnErrors($item);

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
        }

        // form attributes
        $item['form_attributes'] = array_merge(
            $this->config->get("builder_form_base_form_attributes", []),
            $item['form_attributes']
        );

        if($item['type'] === $item['subtype']) $item['subtype'] = 'base';

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
