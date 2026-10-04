<?php
function get_admin_form_textarea($item, $formType = '')
{
    if($item['sub_type'] === 'quill') {
        if($formType === 'side') {
            return form_textarea(
                [
                    'name' => $item['field'],
                    'id' => $item['id'],
                    'rows' => $item['attributes']['rows']
                ],
                set_admin_form_value($item['field'], $item['default'], null),
                $item['attributes']
            );
        }else{
            $output = form_input(
                [
                    'type' => 'hidden',
                    'name' => $item['field'],
                    'id'   => $item['id'],
                ],
                set_admin_form_value($item['field'], $item['default'], null),
                [
                    'data-textarea-id' => "{$item['id']}-quill",
                ]
            );
            $output .= convert_selector_to_html("div#{$item['id']}-quill.textarea-quill.ms-0", true, set_admin_form_value($item['field'], $item['default'], null));
            return $output;
        }
    }else{
        return form_textarea(
            [
                'name' => $item['field'],
                'id' => $item['id'],
                'rows' => $item['attributes']['rows']
            ],
            set_admin_form_value($item['field'], $item['default'], null),
            $item['attributes']
        );
    }
}

function get_admin_form_choice($item, $formType = '')
{
    $wrapClassList = ['choice-wrapper', 'd-flex', 'flex-wrap', 'px-3'];
    $inner = '';

    $direction = $item['form_attributes']['option_stack'] ?? 'horizon';
    if(!isset($item['form_attributes']['wrap_with_border'])) {
        $withBorder = false;
    }else{
        $withBorder = $item['form_attributes']['wrap_with_border'];
    }

    if($item['subtype'] === 'single') {
        $wrapClassList = array_merge($wrapClassList, [
            'option-stack-vertical', 'align-items-start', 'justify-content-start', 'flex-column', 'pt-4', 'pb-1'
        ]);

        if($formType !== 'side') $wrapClassList[] = 'mb-3';

        if(array_key_exists('options', $item)) {
            foreach ($item['options'] as $value=>$text) {
                $id = $item['field'];
                $input = $item['type'] === 'radio'?get_admin_form_radio($item, $value, $text):get_admin_form_checkbox($item, $value, $text);
                $inner .= convert_selector_to_html("label.form-check[for='$id']", true, $input);
            }
        }else if($item['type'] === 'checkbox') {
            $id = $item['field'];
            $input = get_admin_form_checkbox($item, 1, '');
            $inner .= convert_selector_to_html("label.form-check[for='$id']", true, $input);
        }
    }else{
        if($direction === 'vertical') {
            $wrapClassList = array_merge($wrapClassList, [
                'option-stack-vertical', 'align-items-start', 'justify-content-start', 'flex-column'
            ]);
        }else{
            $wrapClassList = array_merge($wrapClassList, [
                'option-stack-horizon', 'justify-content-start'
            ]);
        }

        $count = 0;
        foreach ($item['options'] as $value=>$text) {
            $id = $item['field'].'_'.($count+1);
            $labelClassList = ['form-check', 'me-3'];

            if($direction === 'vertical' && $count === count($item['options']) - 1) $labelClassList[] = 'mb-0';

            $input = $item['type'] === 'radio'?get_admin_form_radio($item, $value, $text, $id):get_admin_form_checkbox($item, $value, $text, $id);
            $inner .= convert_selector_to_html("label.".implode('.', $labelClassList)."[for=\"$id\"]", true, $input);

            $count++;
        }
    }

    if($withBorder) {
        $wrapClassList = array_merge($wrapClassList, [
            'px-3', 'border', 'border-input', 'rounded-3',
        ]);
    }

    return convert_selector_to_html('div.'.implode('.', $wrapClassList), true, $inner);
}

function get_admin_form_checkbox($item, $value, $text, $id = null)
{
    if($id === null) $id = $item['field'];
    $name = $item['name'].($item['subtype'] === 'single'?'':'[]');

    $output = form_checkbox([
        'name' => $name,
        'value' => $value,
        'checked' => false,
        'id' => $id,
        'class' => 'form-check-input',
    ], '', false, $item['attributes']);

    if($text) {
        $output .= "<span class='form-check-label'>".lang($text)."</span>";
    }

    return $output;
}

function get_admin_form_radio($item, $value, $text, $id = null)
{
    if($id === null) $id = $item['field'];
    $name = $item['name'];

    $output = form_radio([
        'name' => $name,
        'value' => $value,
        'checked' => false,
        'id' => $id,
        'class' => 'form-check-input',
    ], '', false, $item['attributes']);
    $output .= "<span class='form-check-label'>".lang($text)."</span>";
    return $output;
}

function get_admin_form_onoff($item, $formType)
{
    $ci =& get_instance();
    return $ci->load->view('builder/layout/onoff', $item, true);
}

function get_admin_form_text($data, $add_class = array(), $attributes = array()): string
{
    $ci =& get_instance();

    $text = $data['form_text'];

    $default['class'] = [
        'form-text'
    ];

    if(!$text){
        $line = '';
        $default['class'][] = 'd-none';
    }else{
        $line = $ci->lang->line(is_array($text)?$text:'form_text.'.$text);
        $icon = $text['icon'] ?? '';
        if($icon) $line = get_icon($icon, false, $text['icon_size'] ?? '', '.ms-1.align-middle').convert_selector_to_html('span.mx-1.align-middle', true, $line);
    }

    if(isset($data['form_attributes'])){
        if(!is_empty($data['form_attributes'], 'sample_file')) {
            $line .= '('.anchor($data['form_attributes']['sample_file'], 'Sample', ['download' => 'sample']).')';
        }
    }

    if(is_string($add_class)){
        $default['class'][] = $add_class;
    }else{
        $default['class'] = array_merge($default['class'], $add_class);
    }

    $default['class'] = implode(' ', $default['class']);

    return '<div '._parse_form_attributes($attributes, $default).'>'.$line.'</div>';
}

function get_admin_form_list_item($item, $formType, $below = true, $disk = false, $label = false): string
{
    if($item['form_attributes']['with_list']) {
        $classList = ['form-list-item-wrap', 'mb-0', 'p-2', 'bg-lighter', 'rounded-3', 'w-px-400', 'mw-100'];
        $classList[] = $disk?'list-styled':'list-unstyled';
        if($item['subtype'] === 'readonly') {
            $classList[] = 'form-list-item-wrap_readonly';
        }elseif($item['form_attributes']['list_sorter']) {
            $classList = array_merge($classList, ['list-group', 'list-group-flush', 'form-list-item-wrap_sorter']);
        }
        $classList = implode('.', $classList);
        $inner = convert_selector_to_html("ul#{$item['id']}-list.$classList");
        if($item['subtype'] === 'readonly') {
            $inner .= form_label(lang([
                'line' => 'Uploads List',
                'replace' => lang($item['label']),
            ]), $item['id'].'-list');
        }
        if($formType === 'side') {
            $inner = convert_selector_to_html('div.form-floating.form-floating-outline', true, $inner);
        }
        if($label) $inner .= form_label(lang($item['label'].'-list'), $item['id'].'-list');
        return convert_selector_to_html('div.input-group.input-group-merge'.($below?'.mt-2':'.mb-2'), true, $inner);
    }else{
        return '';
    }
}

function get_admin_form_ico($item, $formType = 'page', $size = 18): string
{
    if($item['icon'] === 'none') return '';

    if($formType === 'page') {
        if($item['type'] === 'number' && $item['subtype'] === 'unit') return '';
    }

    return create_admin_form_ico($item, $size);
}

function create_admin_form_ico($item, $size = 18): string
{
    if(strpos($item['icon'], 'svg:') !== false) {
        $classname = str_replace('svg:', '', $item['icon']);
        $svg = true;
    }else{
        $classname = get_admin_form_ico_classname($item);
        $svg = false;
    }

    if($classname) {
        $inner = get_icon($classname, $svg, $size);
        return convert_selector_to_html("span#{$item['field']}-ico.input-group-text.text-primary.border-end-0", true, $inner);
    }else{
        return '';
    }
}

function get_admin_form_ico_classname($item): ?string
{
    if($item['icon'] !== null) return $item['icon'];
    if($item['type'] === 'select') return '';

    $rules = preg_split('/\|(?![^\[]*\])/', $item['rules']);
    if(in_array('integer', $rules)) return 'ri-number-2';
    if(in_array('numeric', $rules)) return 'ri-number-2';
    if(in_array('readonly', $rules)) return '';

    return get_icon_classname_by_type($item['type']);
}

function get_admin_form_attributes($item, $form_type = 'side', $form_sub_type = 'base'): array
{
    $ci =& get_instance();

    // initiate
    if(!is_empty($item['attributes'], 'placeholder')){
        $exploded = explode('.', $item['attributes']['placeholder']);
        if(count($exploded) > 1){
            $placeholder = $ci->lang->line($item['attributes']['placeholder']);
        }else{
            $placeholder = $ci->lang->line('placeholder.'.$item['attributes']['placeholder']);
            if($placeholder === 'placeholder.'.$item['attributes']['placeholder'])
                $placeholder = $ci->lang->line($item['attributes']['placeholder']);
        }
    }else{
        $placeholder = $ci->lang->line($item['label']);
    }

    $attributes = [
        'placeholder' => $placeholder,
        'aria-label' => $ci->lang->line($item['label']),
        'aria-describedby' => $item['field'].'-ico',
    ];
    $classList = ['form-control', 'dt-'.$item['field'], 'form-input_'.$item['category']];

    // form_attributes
    if($item['field'])
        $item['form_attributes']['form_field'] = $item['field'];

    if($item['form_attributes']['with_btn'])
        $classList[] = 'form-input_with-button';

    if($item['type'] === 'hidden' || isset($item['attributes']['readonly']) || $item['subtype'] === 'readonly') {
        $item['form_attributes']['detect_changed'] = false;
    }

    foreach ($item['form_attributes'] as $key=>$val) {
        if($val === false) $val = 0;
        $key = 'data-'.str_replace('_', '-', $key);
        if(is_array($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
        $val = str_replace('"', '\'', $val);
        $item['attributes'][$key] = $val;
    }

    // type & subtype
    $classList[] = 'form-input_'.$item['type'].'-'.$item['subtype'];

    // autocomplete false
    if(in_array($item['name'], ['name','email','tel'])) {
        $attributes['autocomplete'] = 'false';
    }

    // add attr by subtype and type
    if($item['type'] === 'text') {
        switch ($item['subtype']) {
            case 'readonly' :
                $attributes['readonly'] = 'readonly';
                break;
            case 'cleave-bizno' :
                $classList[] = 'cleave cleave-bizno';
                $attributes['placeholder'] = '123-45-67890';
                $ci->addFormAssets('cleave');
                break;
        }
    }

    if($item['type'] === 'number') {
        switch ($item['subtype']) {
            case 'readonly' :
                $attributes['readonly'] = 'readonly';
                break;
        }
    }

    if($item['type'] === 'color') {
        switch ($item['subtype']) {
            case 'coloris' :
                $item['type'] = 'text';
                $classList[] = 'coloris';
                $attributes['data-coloris'] = 1;
                $ci->addFormAssets('coloris');
                break;
        }
    }

    if($item['type'] === 'select') {
        $classList[] = 'form-select';
        switch ($item['subtype']) {
            case 'selectpicker' :
                $classList = array_diff(array_merge($classList, [
                    'w-100', 'selectpicker',
                ]), ['form-control']);
                $ci->addFormAssets('selectpicker');
                break;
            case 'select2' :
                $classList = array_diff(array_merge($classList, [
                    'select2',
                ]), ['form-control']);
                $ci->addFormAssets('select2');
                break;
            case 'select2-repeater' :
                $classList = array_diff(array_merge($classList, [
                    'select2-repeater',
                ]), ['form-control']);
                $ci->addFormAssets('select2');
                break;
        }
    }

    if($item['type'] === 'textarea') {
        switch ($item['subtype']) {
            case 'autosize' :
                $classList[] = 'textarea-autosize';
                $attributes['rows'] = 2;
                break;
            default :
                $classList[] = 'h-px-100';
                $attributes['rows'] = $form_type === 'side'?3:5;
                break;
        }
    }

    if($item['type'] === 'tel') {
        switch ($item['subtype']) {
            case 'cleave-hp' :
                $classList[] = 'cleave cleave-hp';
                $attributes['placeholder'] = '010-1234-5678';
                $ci->addFormAssets('cleave');
                break;
            case 'cleave-fulldate' :
                $classList[] = 'cleave cleave-fulldate';
                $attributes['placeholder'] = 'YYYY-MM-DD';
                $ci->addFormAssets('cleave');
                break;
            default :
                break;
        }
    }

    if($item['type'] === 'date') {
        switch ($item['subtype']) {
            case 'flatpickr' :
                $classList[] = 'flatpickr flatpickr-date';
                $attributes['placeholder'] = 'YYYY-MM-DD';
                $ci->addFormAssets('flatpickr');
                break;
            case 'cleave-year' :
                $classList[] = 'cleave cleave-year';
                $attributes['placeholder'] = 'YYYY';
                $ci->addFormAssets('cleave');
                break;
            case 'cleave-month' :
                $classList[] = 'cleave cleave-month';
                $attributes['placeholder'] = 'MM';
                $ci->addFormAssets('cleave');
                break;
            case 'cleave-date' :
                $classList[] = 'cleave cleave-date';
                $attributes['placeholder'] = 'DD';
                $ci->addFormAssets('cleave');
                break;
            default :
                break;
        }
    }

    if($item['type'] === 'time') {
        switch ($item['subtype']) {
            case 'flatpickr' :
                $classList[] = 'flatpickr flatpickr-time';
                $ci->addFormAssets('flatpickr');
                break;
            case 'cleave-time' :
                $classList[] = 'cleave cleave-time';
                $attributes['placeholder'] = 'hh:mm';
                $ci->addFormAssets('cleave');
                break;
            case 'cleave-hour' :
                $classList[] = 'cleave cleave-hour';
                $attributes['placeholder'] = 'hh';
                $ci->addFormAssets('cleave');
                break;
            case 'cleave-minute' :
                $classList[] = 'cleave cleave-minute';
                $attributes['placeholder'] = 'mm';
                $ci->addFormAssets('cleave');
                break;
            default :
                break;
        }
    }

    if($item['type'] === 'year_month') {
        switch ($item['subtype']) {
            case 'flatpickr' :
                $classList[] = 'flatpickr flatpickr-year-month';
                break;
        }
    }

    if($item['type'] === 'file') {
        switch ($item['subtype']) {
            case 'base' :
            case 'single' :
            case 'thumbnail' :
                break;
            case 'multiple' :
                $attributes['multiple'] = 'multiple';
                break;
            case 'readonly' :
                $classList[] = 'd-none';
                break;
            case 'dropzone-full' :
                $classList[] = 'input-dropzone d-none';
                $ci->addFormAssets('dropzone');
                break;
        }
    }

    if($item['type'] === 'checkbox') {
        switch ($item['subtype']) {
            case 'onoff' :
                $attributes['data-custom-toggle'] = 1;
                $item['options'] = [
                    'Y' => 'Y'
                ];
                $ci->addFormAssets('custom-toggle');
                break;
        }
    }

    if($item['type'] === 'custom') {
        $classList[] = 'form-input_custom';
        switch ($item['subtype']) {
            case 'tag-base' :
                $classList[] = 'tagify h-auto';
                $ci->addFormAssets('tagify');
                break;
            case 'domain_register' :
                $ci->addFormAssets('domain-register');
                break;
            case 'ip_register' :
                $ci->addFormAssets('ip-register');
                break;
            case 'dropzone' :
                break;
        }
    }

    // group category
    if($item['group'] !== 'base' && $item['group_attributes']['group_repeater']) {
        foreach (['select2'] as $key) {
            $key = array_search($key, $classList);
            if($key) $classList = array_replace($classList, [$key => $key.'-repeater']);
        }
    }

    // rules
    if(isset($item['rules'])) {
        $rules = preg_split('/\|(?![^\[]*\])/', $item['rules']);
        if(in_array('required', $rules)) $attributes['required'] = 'required';

        if($matches = preg_grep('/^exact_length\[\d+\]$/', $rules)) {
            $option = reset($matches);
            if (preg_match('/^exact_length\[(\d+)\]$/', $option, $matches)) {
                $number = $matches[1];
                $attributes['minlength'] = $number;
                $attributes['maxlength'] = $number;
            }
        }

        if($matches = preg_grep('/^min_length\[\d+\]$/', $rules)) {
            $option = reset($matches);
            if (preg_match('/^min_length\[(\d+)\]$/', $option, $matches)) {
                $number = $matches[1];
                $attributes['minlength'] = $number;
            }
        }

        if($matches = preg_grep('/^max_length\[\d+\]$/', $rules)) {
            $option = reset($matches);
            if (preg_match('/^max_length\[(\d+)\]$/', $option, $matches)) {
                $number = $matches[1];
                $attributes['maxlength'] = $number;
            }
        }

        if($matches = preg_grep('/^min\[\d+\]$/', $rules)) {
            $option = reset($matches);
            if (preg_match('/^min\[(\d+)\]$/', $option, $matches)) {
                $number = $matches[1];
                $attributes['min'] = $number;
            }
        }

        if($matches = preg_grep('/^max\[\d+\]$/', $rules)) {
            $option = reset($matches);
            if (preg_match('/^max\[(\d+)\]$/', $option, $matches)) {
                $number = $matches[1];
                $attributes['max'] = $number;
            }
        }
    }

    // default value
    if(isset($item['form_attributes']['set_default_first'])
        && $item['form_attributes']['set_default_first'] === true
        && isset($item['options'])
        && count($item['options']) > 0
    ) {
        $item['default'] = array_key_first($item['options']);
    }
    if(strlen($item['default'])) $attributes['data-default-value'] = $item['default'];

    // class
    $attributes['class'] = implode(' ', $classList);

    //attributes

    $item['attributes'] = array_merge($item['attributes'], $attributes);

    return $item;
}

function restructure_admin_form_data($form_data, $form_type = 'side', $form_sub_type = 'base'): array
{
    // attributes 처리
    $form_data = array_map(function($item) use($form_type, $form_sub_type) {
        unset($item['list_attributes']);
        if($item['type'] !== 'common') $item = get_admin_form_attributes($item, $form_type, $form_sub_type);
        return $item;
    }, $form_data);

    // group 처리
    $groups = array_unique(array_filter(array_column($form_data, 'group'), function ($item) {
        return $item !== 'base';
    }));

    if(count($groups) > 0) {
        $ci =& get_instance();
        $diff = 0;
        foreach ($groups as $idx => $group_name) {
            if(!$group_name) continue;
            if(!isset($form_data[$idx]['group_attributes'])) continue;
            $attr = $form_data[$idx]['group_attributes'];

            // idx 모두 가져오기
            $indexes = array_keys(array_filter(array_column($form_data, 'group')), $group_name);

            // group이 2개 이상일 때 앞선 배열처리로 인해 list type의 value가 사라지므로,
            // array_column 시 이미 group 처리된 배열은 제외되서 나타남.
            // 이를 보정하기 위해 diff 를 각 key 값에 더해줌.
            $indexes = array_map(function($item) use($diff) {
                return $item+$diff;
            }, $indexes);
            $intersects = array_values(array_intersect_key($form_data, array_flip($indexes)));

            $data = [];
            foreach ($intersects as $i=>$item) {
                if(array_key_exists('group_attributes', $item) && !is_empty($item['group_attributes'], 'key')){
                    $key = $item['group_attributes']['key'];
                }else{
                    $key = $group_name.($i+1);
                }
                $data[$key] = $item;
            }

            switch ($attr['type']) {
                case 'carousel_simple' :
                    $ci->addFormAssets('custom-carousel-simple');
                    break;
            }

            $form_data = array_diff_key($form_data, array_flip($indexes));

            // 20260923 tab_index 추가
            if(array_key_exists('tab_index', $attr)) {
                $tab_index = $attr['tab_index'];
                unset($attr['tab_index']);
            }else{
                $tab_index = 0;
            }

            $form_data[$idx] = [
                'id' => $group_name,
                'category' => 'group',
                'group' => $group_name,
                'label' => $attr['label'],
                'form_text' => $attr['form_text'],
                'type' => $attr['type'] ?? 'base',
                'attr' => $attr,
                'data' => $data,
                'colspan' => $attr['colspan'] ?? 12,
                'tab_index' => $tab_index,
            ];

            $form_data[$idx]['view'] = $form_data[$idx]['type'];
            ksort($form_data);
            $diff += count($indexes)-1;
        }
    }

    return array_values($form_data);
}

function set_dropdown_default_value($item)
{
    $default = $item['attributes']['data-set-default-first'] ? array_key_first($item['options']) : $item['default'];
    return set_admin_form_value($item['field'], $default, null);
}

function set_admin_form_value($field, $default = '', $view = null, $html_escape = TRUE)
{
    if($view) {
        return array_key_exists($field, $view)?$view[$field]:'';
    }else{
        return set_value($field, $default, $html_escape);
    }
}

function get_help_block($data): string
{
    $defaults = array_merge(array(
        'tag' => 'span',
        'text' => '',
    ), $data);

    return "<{$defaults['tag']} "._attributes_to_string($data['attr']).">".$defaults['text']."</{$defaults['tag']}>";
}

function trans_formdata_dit_type($form_data): array
{
    $list = [];
    foreach ($form_data as $item) {
        if($item['group'] !== 'base') {
            $list[$item['group']] = $item;
        }else{
            if(!$item['field']) continue;
            $list[$item['field']] = $item;
        }
    }
    return $list;
}

function get_form_input_by_type($item, $formType): string
{
    switch ($item['type']) {
        case 'password' :
            return form_password(
                [
                    'name' => $item['name'],
                    'id' => $item['id'],
                ],
                set_admin_form_value($item['field'], $item['default']??'', null),
                $item['attributes']
            );
        case 'checkbox' :
            if($item['subtype'] === 'onoff') {
                return get_admin_form_onoff($item, $formType);
            }else{
                return get_admin_form_choice($item, $formType);
            }
        case 'radio' :
            if($formType === 'side') {
                return get_admin_form_radio($item, $formType);
            }else{
                return get_admin_form_choice($item, $formType);
            }
        case 'select' :
            return form_dropdown(
                $item['name'],
                $item['options'] ?? [],
                set_admin_form_value($item['field'], $item['default']??'', null),
                array_merge([
                    'id' => $item['id'],
                    'data-style' => 'btn-default'
                ], $item['attributes'])
            );
        case 'textarea' :
            return form_textarea(
                [
                    'name' => $item['name'],
                    'id' => $item['id'],
                    'rows' => $item['attributes']['rows']
                ],
                set_admin_form_value($item['field'], $item['default']??'', null),
                $item['attributes']
            );
        case 'file' :
            return form_upload([
                'name' => $item['name'],
                'id' => $item['id'],
            ], $item['attributes']);
        case 'number' :
            if($item['subtype'] === 'unit') {
                if($formType === 'side') {
                    return form_input(
                        [
                            'type' => $item['type'],
                            'name' => $item['name'],
                            'id' => $item['id'],
                        ],
                        set_admin_form_value($item['field'], $item['default']??'', null),
                        $item['attributes']
                    );
                }else{
                    $html = create_admin_form_ico($item);
                    $html .= form_input(
                        [
                            'type' => $item['type'],
                            'name' => $item['name'],
                            'id' => $item['id'],
                        ],
                        set_admin_form_value($item['field'], $item['default']??'', null),
                        $item['attributes']
                    );
                    $html .= "<span class='input-group-text border-left-0 input-group-text-unit text-primary'>{$item['form_attributes']['unit']}</span>";
                }
                return $html;
            }else{
                return form_input(
                    [
                        'type' => $item['type'],
                        'name' => $item['name'],
                        'id' => $item['id'],
                    ],
                    set_admin_form_value($item['field'], $item['default']??'', null),
                    $item['attributes']
                );
            }
        default :
            return form_input(
                [
                    'type' => $item['type'],
                    'name' => $item['name'],
                    'id' => $item['id'],
                ],
                set_admin_form_value($item['field'], $item['default']??'', null),
                $item['attributes']
            );
    }
}

function get_page_form_input_by_type($item, $formType): string
{
    $html = get_form_input_by_type($item, $formType);
    if($item['form_attributes']['with_btn']) {
        switch ($item['form_attributes']['btn_type']) {
            case 'dup_check' :
                $html .= form_button([
                    'data-rel-field' => $item['field'],
                    'type' => 'button',
                    'class' => 'btn btn-outline-primary waves-effect btn-dup-check',
                ], lang('Check'), [
                    'onclick' => "checkDuplicate(this)",
                    'disabled' => 'disabled',
                ]);
                break;
        }
    }
    return $html;
}

function get_side_form_input_by_type($item, $formType): string
{
    $html = get_form_input_by_type($item, $formType);
    if(!(($item['type']==='checkbox'||$item['type']==='radio')&&$item['subtype']==='single'))
        $html .= form_label(lang($item['label']), $item['id']);
    return $html;
}

function get_side_form_input_adds($item, $formType): string
{
    if($item['type'] === 'number' && $item['subtype'] === 'unit') {
        return "<span class='input-group-text border-left-0 input-group-text-unit text-primary'>{$item['form_attributes']['unit']}</span>";
    } else {
        return '';
    }
}

function restructure_form_data_by_type($form_data, $form_type = 'side', $form_sub_type = 'base'): array
{
    $form_data = restructure_admin_form_data($form_data, $form_type, $form_sub_type);
    return reformat_form_data_by_type($form_data, $form_type, $form_sub_type);
}

function reformat_form_data_by_type($form_data, $form_type = 'side', $form_sub_type = 'base'): array
{
    if($form_type === 'custom') {
        return trans_formdata_dit_type($form_data);
    }

    $data = [
        'hiddens' => array_values(array_filter($form_data, function($item) {
            return $item['type'] === 'hidden';
        })),
        'fields' => [],
    ];

    $fields = array_map(function($item) use ($form_type, $form_sub_type) {
        if(is_empty($item, 'colspan')) {
            $item['colspan'] = $form_sub_type === 'grid' ? 6 : 12;
        }
        return $item;
    }, array_values(array_filter($form_data, function($item) use ($form_type, $form_sub_type) {
        return $item['type'] !== 'hidden' && !($form_sub_type !== 'grid' && $item['type'] === 'common');
    })));

    if($form_type === 'tabs') {
        $tab_indexes = array_column($fields, 'tab_index');
        for($i = min($tab_indexes); $i <= max($tab_indexes); $i++) {
            $tab_fields = array_values(array_filter($fields, function($item) use ($i) {
                return $item['tab_index'] === $i;
            }));
            $data['fields'][$i] = set_form_field_data($tab_fields, $form_sub_type);
        }
    }else{
        $data['fields'] = set_form_field_data($fields, $form_sub_type);
    }

    return $data;
}

function set_form_field_data($fields, $form_sub_type)
{
    switch ($form_sub_type) {
        case 'grid' :
            return set_form_rows($fields);
        default :
            return $fields;
    }
}

function set_form_rows($fields): array
{
    $result = [];

    $cols = 0;
    $rows = 0;
    foreach (array_column($fields, 'colspan') as $key=>$colspan){
        if($cols + $colspan > 12) {
            $cols = 0;
            $rows++;
        }
        $result[$rows][] = $fields[$key];
        $cols += $colspan;
    }

    return $result;
}

function reformat_filter_data($filterData, $filterConfig): array
{
    $rowColumns = 0;
    $rowIdx = 0;
    $list = [];
    foreach ($filterData as $idx=>$filter) {
        if($rowColumns >= 12) {
            $rowColumns = 0;
            $rowIdx++;
        }

        $list[$rowIdx][] = $filter;
        $rowColumns += $filter['colspan'];
    }

    if(count($list)) {
        // lastRow
        $lastRowColumns = $rowColumns;
        if($lastRowColumns + FILTER_BASE_COLSPAN > 12) {
            $rowIdx++;
            $list[$rowIdx] = [
                ['type' => 'common', 'subtype' => 'space', 'colspan' => 9],
            ];
            $lastRowColumns = 9;
        }

        $remains = 12 - $lastRowColumns - FILTER_BASE_COLSPAN;
        if($remains > 0) {
            $list[$rowIdx][] = ['type' => 'common', 'subtype' => 'space', 'colspan' => $remains];
        }

        $list[$rowIdx][] = [
            'type' => 'common',
            'subtype' => 'submit',
            'search_btn' => $filterConfig['actions']['submit'],
            'reset_btn' => $filterConfig['actions']['reset'],
        ];
    }

    return $list;
}

function get_builder_form_label($item, $attr = []): string
{
    $required = isset($item['attributes']['required']) && $item['attributes']['required'] === 'required';
    $id = $item['id']??'';
    $label = lang($item['label']).($required?'<span class="ms-2 text-danger">*</span>':'');
    return form_label($label, $id, $attr);
}

function set_dropzone_attributes($attributes = []): string
{
    return _parse_form_attributes([
        'dz-style' => $attributes['style'] ?? 'base',
        'dz-max-files' => $attributes['max'] ?? 1,
        'dz-max-filesize' => $attributes['max_size'] ?? '',
        'dz-accepted-files' => $attributes['accept'] ?? '',
        'dz-multiple' => $attributes['multiple'] ?? 'false',
    ], [
        'dz-dict-default-message' => lang('Drop file here or click to upload'),
        'dz-dict-remove-file' => lang('Delete'),
    ]);
}

function get_form_item_id($field, $prefix = '')
{
    $field = str_replace('.', '_', $field);
    return ($prefix ? : '').$field;
}
