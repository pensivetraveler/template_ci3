<?php defined('BASEPATH') OR exit('No direct script access allowed');

$config['builder_view_config_loaded'] = true;

$config['builder_view_config'] = [
    'hiddens' => [
        [
            'field' => '',
            'label' => '',
        ]
    ],
    'fields' => [
        [
            'field' => '',
            'label' => '',
            'category' => 'base',
            'type' => 'text',
            'subtype' => 'base',
            'attributes' => [

            ],
            'help_block' => [
                'tag' => 'span',
                'text' => '',
                'attr' => [
                    'class' => '',
                ],
            ],
            'colspan' => 6,
        ]
    ],
    'actions' => [
        [
            'text' => 'Check',
            'icon' => '',
            'onclick' => [
                'kind' => 'messageRead',
                'noValue' => true,
                'attrs' => [
                    'toggle' => 'popover',
                    'trigger' => 'hover',
                    'placement' => 'right',
                    'html' => 'true',
                    'template' => '<div class="popover" role="tooltip"><div class="popover-arrow"></div><div class="popover-body p-0 rounded-3 overflow-hidden"></div></div>',
                ],
            ]
        ]
    ],
    'buttons' => [
        'list' => true,
        'edit' => true,
        'delete' => true,
    ],
];

$config['builder_view_field_config'] = [
    'field' => '',
    'label' => '',
    'category' => 'base',
    'type' => 'text',
    'subtype' => 'base',
    'attributes' => [],
    'help_block' => [],
    'colspan' => 6,
];

$config['builder_view_hidden_config'] = array_replace_recursive($config['builder_view_field_config'], [
    'category' => 'base',
    'type' => 'hidden',
    'subtype' => 'base',
]);

$config['builder_view_actions_config'] = [
    'list' => true,
    'edit' => true,
    'delete' => true,
];

$config['builder_view_buttons_config'] = [];