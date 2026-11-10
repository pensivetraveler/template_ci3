<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 목록 화면: 칸·버튼·필터·내보내기 설정과 스크립트 (MY_Builder_WEB)
 */
trait BuilderWebListTrait
{
    protected function prepareListData($data): array
    {
        $data['backLink'] = WEB_HISTORY_BACK;
        $data['filters'] = reformat_filter_data($this->jsVars['FILTER_COLUMNS']??[], $this->filterConfig);
        $data['filterHelpBlock'] = $this->filterConfig['help_block'] ?? [];
        $data['columns'] = $this->jsVars['LIST_COLUMNS']??[];
        $data['isCheckbox'] = $this->methodConfig['properties']['isCheckbox'];
        $data['actions'] = reformat_bool_type_list($this->methodConfig['actions']);
        $data['buttons'] = $this->methodConfig['buttons']??[];

        $data['formExist'] = false;
        if($this->methodConfig['properties']['formExist']) {
            $data['formExist'] = true;
            $data['formData'] = count($this->formColumns)>0?restructure_form_data_by_type($this->jsVars['FORM_DATA']):[];
            $data['formType'] = count($this->formColumns)>0?$this->methodConfig['properties']['formType']:'';

            if(count($this->formColumns)>0) {
                $data['formSubType'] = $this->methodConfig['properties']['formSubType']??'base';
            }else{
                $data['formSubType'] = '';
            }

            $data['formStyle'] = count($this->formColumns)>0?$this->methodConfig['properties']['formStyle']:'';
            $this->addFormScripts();
        }else{
            foreach ($data['actions'] as $i=>$action) {
                if($action === 'delete') continue;
                if(!in_array($action, $this->routeConfig['properties']['allows'])) unset($data['actions'][$i]);
            }
            $data['actions'] = array_values($data['actions']);
        }

        // 모달 상세 보기 (setJSVariables 가 VIEW_COLUMNS 를 만들었을 때)
        $data['viewExist'] = false;
        if(!empty($this->methodConfig['properties']['viewConfig'])) {
            $data['viewExist'] = true;
            $data['viewType'] = 'modal';
            $data['viewData'] = reformat_form_data_by_type($this->jsVars['VIEW_COLUMNS'], 'page', 'modal');

            // 입력 요소가 있으면 폼 자산이 필요 (옆 폼이 있으면 이미 붙음)
            if(!$data['formExist'] && count(array_filter($this->viewColumns, function($item) {
                return !in_array($item['type'], ['view', 'hidden', 'common'], true);
            }))) {
                $this->addFormScripts();
            }

            $this->addJS['tail'][] = [
                base_url('public/assets/builder/js/app-page-view.js'),
            ];
        }

        if(!array_key_exists('subPage', $data))
            $data['subPage'] = 'builder/layout/list';

        $this->addListScripts($this->methodConfig['subtype']);

        return $data;
    }

    protected function getListColumns($name = null): array
    {
        if(isset($name)) {
            $config = $this->config->get($name, []);
        }else{
            $config = $this->resourceLoader->entityConfig($this->setConfigList(
                'list', $this->methodConfig['resourceConfig'] ?? null
            ), [], false);
        }
        $formConfig = $this->resourceLoader->entityConfig($this->setConfigList('form', $this->methodConfig['properties']['formConfig'] ?? null), [], false) ?: [];
        // list 설정이 없으면 form 설정의 list => true 항목으로 (API 와 같은 규칙, getListConfigFromForm)
        if(empty($config) && !isset($name)) $config = $this->getListConfigFromForm($formConfig);
        if(empty($config)) show_error(__METHOD__." : List Config is Empty For ".$this->router->location);

        // 목록 항목에 option_attributes 가 없으면 같은 필드의 form 설정 값을 쓴다 (셀에 코드 대신 라벨 표시)
        $formOptions = [];
        foreach ($formConfig as $formItem) {
            if(isset($formItem['field']) && !is_empty($formItem, 'option_attributes')) $formOptions[$formItem['field']] = $formItem['option_attributes'];
        }

        $this->listColumns = array_reduce($config, function($carry, $item) use ($formOptions) {
            $item = array_merge($this->config->get("builder_list_base", []), $item);

            if(is_empty($item, 'option_attributes') && isset($item['field'], $formOptions[$item['field']]))
                $item['option_attributes'] = $formOptions[$item['field']];

            // 폼·필터와 같이 option_field 가 있으면 그 필드로 옵션을 찾는다
            if(!is_empty($item, 'option_attributes'))
                $item['options'] = $this->getOptions($item['option_attributes']['option_field'] ?? $item['field'], $item['option_attributes']);

            if($item['type'] === 'hidden') $item['list'] = false;

            $carry[] = $item;
            return $carry;
        }, []);

        return array_column(array_filter($this->listColumns, function ($item) {
            return $item['list'];
        }), 'field');
    }

    protected function setListColumns($isRowNumber = true, $listActionExist = true): array
    {
        $columns = $this->getListColumns();

        $list = array_reduce(array_keys($columns), function($carry, $key) use($columns) {
            $field = $columns[$key];
            $idx = array_search($field, array_column($this->listColumns, 'field'));
            if($idx === false) return $carry;

            $item = $this->listColumns[$idx];

            $attributes = array_merge(
                $this->config->get("builder_list_base", []),
                $item['list_attributes'] ?? []
            );

            $label = is_empty($attributes, 'label')?$item['label']:$attributes['label'];
            if(!is_string($label) || $label === '') $label = $this->router->class.'.'.$field;

            if(sscanf($label, 'lang:%s', $line) === 1) $label = $line;

            if($this->lang->line_exists($label.'_list')) $label = $label.'_list';

            // 기본값 < 항목 값 < list_attributes (목록 전용 설정이 가장 우선 — 항목은 이미 기본값과 합쳐져 있어
            // 예전 순서(list_attributes, 항목)에서는 list_attributes 의 type·onclick·render 가 늘 무시됐음)
            $carry[] = array_merge($item, $item['list_attributes'] ?? [], [
                'field' => $field,
                'label' => $label ?? $this->router->class.'.'.$field,
            ]);

            return $carry;
        }, []);

        if(empty($list)) $this->logging(__METHOD__." : list columns for class '{$this->router->class}' are empty.");

        if($isRowNumber) {
            array_unshift($list,
                array_merge(
                    $this->config->get("builder_list_base", []),
                    [
                        'label' => 'lang:common.row_num',
                        'type' => 'row_num',
                    ]
                )
            );
        }

        if($listActionExist) {
            $list[] = array_merge(
                $this->config->get('builder_list_base', []),
                [
                    'label' => 'lang:common.actions',
                    'type' => 'actions',
                ]
            );
        }

        return $list;
    }

    protected function setListButtons($buttons): array
    {
        if(array_key_exists('add', $buttons) && $buttons['add']) {
            $buttons['add'] = is_auth_avaliable('create', $this->pageAuth);
        }
        if(array_key_exists('excel', $buttons) && $buttons['excel']) {
            // 가져오기 화면(excel method)이 있는 route 에서만
            $buttons['excel'] = route_has_excel_method($this->routeConfig['methods'] ?? []) && is_auth_avaliable('import', $this->pageAuth);
        }
        return $buttons;
    }

    protected function setListActions($actions): array
    {
        if(array_key_exists('view', $actions) && $actions['view']) {
            $actions['view'] = is_auth_avaliable('read', $this->pageAuth);
        }
        if(array_key_exists('edit', $actions) && $actions['edit']) {
            $actions['edit'] = is_auth_avaliable('update', $this->pageAuth);
        }
        if(array_key_exists('delete', $actions) && $actions['delete']) {
            $actions['delete'] = is_auth_avaliable('delete', $this->pageAuth);
        }
        // 복제는 새 행을 만드는 동작이므로 등록 권한이 필요
        if(array_key_exists('duplicate', $actions) && $actions['duplicate']) {
            $actions['duplicate'] = is_auth_avaliable('create', $this->pageAuth);
        }
        return reformat_bool_type_list($actions);
    }

    protected function setListExports($exports): array
    {
        $available = is_auth_avaliable('export', $this->pageAuth);
        if(!$available) {
            $exports = array_fill_keys(array_keys($exports), false);
        }
        return $exports;
    }

    protected function setFilterColumns(): array
    {
        $filterConfig = $this->resourceLoader->entityConfig($this->setConfigList(
            'filter', $this->methodConfig['properties']['filterConfig'] ?? null
        ), [], false);
        if(empty($filterConfig)) return [];

        $this->filterConfig = fill_config_properties($filterConfig, $this->config->get('builder_filter_config', []));

        return array_map(function($item) {
            if(!isset($item['colspan'])) $item['colspan'] = FILTER_BASE_COLSPAN;
            if($item['type'] === 'common') return $item;

            $item = array_merge($this->config->get("builder_form_filter_base"), $item);
            if(!isset($item['id'])) $item['id'] = get_form_item_id($item['field'], 'filter-');


            $item['name'] = $item['filter_attributes']['type'];
            if(!is_empty($item['filter_attributes'], 'subtype')) {
                $item['name'] .= '['.$item['filter_attributes']['subtype'].']';
            }else{
                $item['name'] .= '['.$item['field'].']';
            }

            if($item['type'] === 'select') {
                $item['options'] = [];
                $options = $this->getOptions($item['option_attributes']['option_field'] ?? $item['field'], $item['option_attributes']);
                if(in_array($item['option_attributes']['option_type'], ['default', 'bool', 'yn', 'gender'])) {
                    $item['options']['*'] = 'All';
                    foreach($options as $k=>$v) $item['options'][$k] = $v;
                }else{
                    $item['options'] = $options;
                }
            }

            // form attributes
            $item['form_attributes'] = array_merge(
                $this->config->get("builder_form_base_form_attributes", []),
                $item['form_attributes'] ?? []
            );

            return get_admin_form_attributes($item, 'common');
        }, $this->filterConfig['filters']);
    }

    protected function addListScripts($type)
    {
        switch ($type) {
            case 'datatable' :
                $this->addCSS[] = [
                    base_url('public/assets/builder/vendor/libs/datatables-bs5/datatables.bootstrap5.css'),
                    base_url('public/assets/builder/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css'),
                    base_url('public/assets/builder/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css'),
                    base_url('public/assets/builder/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css'),
                ];

                $this->addJS['tail'][] = [
                    base_url('public/assets/builder/vendor/libs/datatables-bs5/datatables-bootstrap5.js'),
                    base_url('public/assets/builder/js/app-page-list.js'),
                ];
                break;
            default :
                break;
        }
    }
}
