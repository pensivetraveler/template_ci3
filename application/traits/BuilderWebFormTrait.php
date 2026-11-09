<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 등록·수정·상세 화면: 입력 값, 상세 칸, 폼 스크립트 (MY_Builder_WEB)
 */
trait BuilderWebFormTrait
{
    /**
     * 설정만으로 그리는 상세 보기 화면인지: category view + type base.
     * type 이 dashboard·statistics 처럼 다르면 컨트롤러·전용 뷰가 그리는 화면이라 이 경로를 타지 않는다.
     * subtype 은 화면 틀(view_{subtype}.php: base·grid·modal·article·comments)
     */
    protected function isDetailView(): bool
    {
        return ($this->methodConfig['category'] ?? '') === 'view' && ($this->methodConfig['type'] ?? '') === 'base';
    }

    protected function prepareViewData($data): array
    {
        $data['viewType'] = $this->methodConfig['subtype'];

        // 2번째 인자는 폼 형태(page), 3번째가 subtype(grid 면 12칸 행으로 나눔)
        $data['viewData'] = reformat_form_data_by_type($this->jsVars['VIEW_COLUMNS'], 'page', $data['viewType']);

        $data['identifier'] = array_filter($this->viewColumns, function ($item) {
            return array_key_exists('field', $item)&&in_array($item['field'], $this->jsVars['IDENTIFIER']);
        });

        // 목록은 항상, 수정은 수정 화면(edit 메서드)과 수정 권한이 있을 때, 삭제는 삭제 권한이 있을 때
        $data['actions'] = array_values(array_filter(reformat_bool_type_list($this->methodConfig['actions']), function ($action) {
            switch ($action) {
                case 'list' : return true;
                case 'edit' : return in_array('edit', $this->routeConfig['properties']['allows']) && is_auth_avaliable('update', $this->pageAuth);
                case 'delete' : return is_auth_avaliable('delete', $this->pageAuth);
                default : return in_array($action, $this->routeConfig['properties']['allows']);
            }
        }));
        $data['buttons'] = $this->methodConfig['buttons']??[];

        $data['isComments'] = $this->methodConfig['properties']['isComments'];

        // 입력 요소가 있을 때만 폼 자산을 붙임 (hidden·common 은 입력 요소가 아님). 댓글 입력 폼(app-page-comment.js)도 FormValidation 을 쓴다
        if($data['isComments'] || count(array_filter($this->viewColumns, function($item) {
            return !in_array($item['type'], ['view', 'hidden', 'common'], true);
        }))) {
            $this->addFormScripts();
        }

        if($data['isComments']) {
            $this->addJS['tail'][] = [
                base_url('public/assets/builder/js/app-page-comment.js')
            ];
        }

        // subPage 를 정하지 않으면 viewApp() 이 {flag}/{class}/view → … → builder/layout/view 순으로 찾는다 (전용 뷰 우선)

        return $data;
    }

    protected function prepareFormData($data): array
    {
        $data['formType'] = $this->methodConfig['type'];
        $data['formSubType'] = $this->methodConfig['subtype'];
        $data['formData'] = restructure_form_data_by_type($this->jsVars['FORM_DATA'], $data['formType'], $data['formSubType'], $this->getFormTabs());

        $data['actions'] = array_values(array_filter(reformat_bool_type_list($this->methodConfig['actions']), function ($action) {
            return $action === 'delete' || in_array($action, $this->routeConfig['properties']['allows']);
        }));
        $data['buttons'] = $this->methodConfig['buttons']??[];

        if(!array_key_exists('subPage', $data))
            $data['subPage'] = 'builder/layout/form';

        $this->addFormScripts();

        return $data;
    }

    /**
     * tabs 레이아웃의 탭 정의 (resolve_form_tabs()). 찾는 순서는 다른 엔티티 설정과 같다:
     * properties.tabsConfig(없으면 resourceConfig) → tabs_{class}_{method}_config → tabs_{class}_config
     */
    protected function getFormTabs(): array
    {
        if(($this->methodConfig['type'] ?? '') !== 'tabs') return [];

        $add = ($this->methodConfig['properties']['tabsConfig'] ?? '') ?: ($this->methodConfig['resourceConfig'] ?: null);
        return resolve_form_tabs(
            $this->resourceLoader->entityConfig($this->setConfigList('tabs', $add), [], false),
            $this->session->userdata('user_cd')
        );
    }

    /**
     * 이 사용자에게 숨긴 탭의 필드는 화면 검증을 끈다 (보이지 않는 칸의 오류로 저장이 막히지 않도록, 서버도 그 값을 받지 않음)
     */
    protected function skipHiddenTabValidation(array $formData): array
    {
        $hidden = get_hidden_tab_fields($formData, $this->getFormTabs());
        if(!count($hidden)) return $formData;

        return array_map(function ($item) use ($hidden) {
            if(isset($item['field']) && in_array($item['field'], $hidden, true)) $item['form_attributes']['form_validation'] = false;
            return $item;
        }, $formData);
    }

    protected function setFormData($formColumns = []): array
    {
        if(empty($formColumns)) $formColumns = $this->formColumns;

        $result = [];
        $groups = [];
        $attr = [];
        foreach ($formColumns as $i=>$item) {
            if(isset($item['type']) && $item['type'] === 'common') {
                $result[] = $item;
                continue;
            }

            if (!$item['form']) continue;

            if ($item['subtype'] === 'identifier' && !in_array($this->router->method, ['index', 'list'])){
                // page type form 에 identifier default 값 부여
                if(end($this->uri->segments) !== $this->router->method)
                    $item['default'] = end($this->uri->segments);
            }

            /**
             * 예외 처리 : textarea 가 wysiwyg quill 인 경우
             */
            if($this->listForm && $item['type'] === 'textarea' && $item['subtype'] !== 'autosize'){
                $item['subtype'] = 'autosize';
            }

            // 페이지 폼의 textarea + quill 은 custom quill 뷰(form_{type}_custom_quill.php)로 그린다
            // (전에는 일반 textarea 로 나왔음). 그 폼 type 의 quill 뷰가 없으면 textarea 그대로
            if(!$this->listForm && $item['type'] === 'textarea' && $item['subtype'] === 'quill') {
                $pageFormType = $this->methodConfig['type'] ?? 'base';
                if(is_file(APPPATH."views/builder/layout/form_{$pageFormType}_custom_quill.php")) $item['type'] = 'custom';
            }

            if ($item['group'] !== 'base') {
                if(!in_array($item['group'], $groups)) {
                    $groups[] = $item['group'];
                    $attr = array_merge($this->config->get("builder_form_base_group_attributes", []), $item['group_attributes']);
                }else{
                    $attr = array_merge(
                        $attr,
                        $item['group_attributes'],
                    );
                }

                // repeater base
                if($attr['type'] === 'base' && $attr['group_repeater']) {
                    $attr['type'] = 'repeater_'.$attr['repeater_type'];
                }

                $item['group_attributes'] = $attr;

                $item['id'] = get_group_field_id($item['group_attributes'], $item['group'], $item['field']);
                $item['name'] = get_group_field_name($item['group_attributes'], $item['group'], $item['field']);

                $item['form_attributes'] = array_merge(
                    $item['form_attributes'],
                    [
                        'group_name' => $item['group'],
                        'group_field' => $item['field'],
                        'group_key' => $item['group_attributes']['key'],
                        'group_view' => $attr['type'],
                    ]
                );
            }else{
                // group category 예외처리
                $item['group_attributes'] = [];
                $item['id'] = get_form_item_id($item['field'], $this->listForm?$this->config->item('form_side_prefix'):$this->config->item('form_page_prefix'));
                $item['name'] = $item['field'];
            }

            // view type
            $item['view'] = $item['subtype'];

            $result[] = $item;
        }

        return $result;
    }

    protected function setViewColumns($name = null, $add = null, string $idPrefix = ''): array
    {
        $config = [];
        if(isset($name)) {
            $config = $this->config->get($name, []);
        }else{
            // view 설정: resourceConfig(또는 $add) → view_{class}_{method}_config → view_{class}_config 순
            $config = $this->resourceLoader->entityConfig($this->setConfigList('view', $add ?? ($this->methodConfig['resourceConfig'] ?: null)), [], false);
            if(empty($config)) {
                // view 설정이 없으면 form 설정의 필드를 읽기 전용(view)으로 보여 준다
                $config = array_values(array_map(function($item) {
                    if(!in_array($item['type'], ['hidden', 'common'], true)) {
                        $item['type'] = 'view';
                        $item['subtype'] = 'base';
                    }
                    return $item;
                }, array_filter($this->setFormColumns($this->methodConfig['properties']['formConfig'] ?: null), function ($item) {
                    return !array_key_exists('form', $item) || $item['form'];
                })));
            }
        }

        if(!empty($config)) {
            $config = array_map(function ($item) use ($idPrefix) {
                if(isset($item['subtype']) && $item['subtype'] === 'identifier') $item['type'] = 'hidden';
                if(!isset($item['category'])) $item['category'] = 'base';
                if(!isset($item['type'])) $item['type'] = 'view';
                if(!isset($item['subtype'])) $item['subtype'] = 'base';

                if($item['type'] === 'common') return $item;

                $item['id'] = $idPrefix.$item['field'];
                // 뷰 템플릿은 lang($item['label']) 로 출력하므로 'lang:' 접두어를 뗀다 (form 설정은 setFormColumn 이 이미 뗌)
                if(isset($item['label']) && is_string($item['label']) && sscanf($item['label'], 'lang:%s', $line) === 1) $item['label'] = $line;
                if($item['type'] === 'hidden'){
                    $item = array_merge($this->config->get('builder_view_hidden_config'), $item);
                }else{
                    if($item['type'] !== 'view') {
                        $item = $this->setFormColumn($item);
                        $item['attributes'] = get_admin_form_attributes($item, 'page');
                    }
                    $item = array_merge($this->config->get('builder_view_base'), $item);
                }

                return $item;
            }, $config);
        }

        return $this->viewColumns = $config;
    }

    protected function addFormScripts()
    {
        $this->addCSS[] = [
            base_url('public/assets/builder/vendor/libs/tagify/tagify.css'),
            base_url('public/assets/builder/vendor/libs/@form-validation/form-validation.css'),
            base_url('public/assets/builder/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.css'),
        ];

        $this->addJS['tail'][] = [
            base_url('public/assets/builder/vendor/libs/autosize/autosize.js'),
            base_url('public/assets/builder/vendor/libs/tagify/tagify.js'),
            base_url('public/assets/builder/vendor/libs/@form-validation/popular.js'),
            base_url('public/assets/builder/vendor/libs/@form-validation/bootstrap5.js'),
            base_url('public/assets/builder/vendor/libs/@form-validation/auto-focus.js'),
            base_url('public/assets/builder/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.js'),
            base_url('public/assets/builder/vendor/libs/jquery-repeater/jquery-repeater.js'),
            base_url('public/assets/builder/vendor/libs/jquery-repeater/jquery-repeater-plugins.js'),
        ];

        // wysiwig
        $this->addCSS[] = [
            base_url('public/assets/builder/vendor/libs/quill/typography.css'),
            base_url('public/assets/builder/vendor/libs/quill/katex.css'),
            base_url('public/assets/builder/vendor/libs/quill/editor.css'),
        ];

        // wysiwig (app-page-preset.js 의 toolbarDefault 를 app-page-form.js 가 quill 툴바로 쓴다 — head.php 에서 빠져 있어 quill 폼이 ReferenceError)
        $this->addJS['tail'][] = [
            base_url('public/assets/builder/vendor/libs/quill/katex.js'),
            base_url('public/assets/builder/vendor/libs/quill/quill.js'),
            base_url('public/assets/builder/js/app-page-preset.js'),
        ];
    }

    public function addFormAssets($assetName)
    {
        if(!in_array($assetName, $this->formAssets)) {
            $this->formAssets[] = $assetName;

            switch($assetName) {
                case 'select2' :
                    $this->addCSS[] = [
                        base_url(BUILDER_ASSET_VENDOR_URI.'/libs/select2/select2.css'),
                    ];

                    $this->addJS['tail'][] = [
                        base_url(BUILDER_ASSET_VENDOR_URI.'/libs/select2/select2.js'),
                    ];
                    break;
                case 'coloris' :
                    $this->addCSS[] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/coloris/coloris.min.css'),
                    ];

                    $this->addJS['tail'][] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/coloris/coloris.min.js'),
                    ];
                    break;
                case 'custom-toggle' :
                    $this->addCSS[] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/custom-toggle/style.css'),
                    ];

//                    $this->addJS['tail'][] = [
//                        base_url(BUILDER_ASSET_LIBS_URI.'/custom-toggle/common.js'),
//                    ];
                    break;
                case 'custom-carousel-simple' :
                    $this->addCSS[] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/custom-carousel-simple/style.css'),
                    ];

                    $this->addJS['tail'][] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/custom-carousel-simple/common.js'),
                    ];
                    break;
                case 'domain-register' :
                    $this->addCSS[] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/domain-register/style.css'),
                    ];

                    $this->addJS['tail'][] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/domain-register/common.js'),
                    ];
                    break;
                case 'ip-register' :
                    $this->addCSS[] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/ip-register/style.css'),
                    ];

                    $this->addJS['tail'][] = [
                        base_url(BUILDER_ASSET_LIBS_URI.'/ip-register/common.js'),
                    ];
                    break;
            }
        }
    }
}
