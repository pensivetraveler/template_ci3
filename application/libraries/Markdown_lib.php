<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Include PHP Third Party files
require_once APPPATH . '/third_party/Markdown/Markdown.php';

class Markdown_lib extends Markdown
{
    protected array $markdown_config;

    function __construct()
    {
        parent::__construct();
        log_message('Debug', 'Markdown Library is loaded.');

        $config_path = isset($params['config']) && !empty($params['config'])?$params['config']:'';
        $this->markdown_config = $this->defaultConfig();
        if($config_path) {
            $CI = &get_instance();
            $CI->load->config($config_path, true, true);
            $this->markdown_config = array_merge(
                $this->markdown_config,
                $CI->config->item($config_path)
            );
        }
    }

    function load($config = [])
    {
        log_message('Debug', 'Third Party Markdown is loaded newly.');
        return new Markdown(array_merge($this->markdown_config, $config));
    }

    protected function defaultConfig(): array
    {
        return [
            'tab_width' => 4,
            'no_markup' => false,
            'no_entities' => false,
            'hard_wrap' => false,
            'predef_urls' => array(),
            'predef_titles' => array(),
            'predef_abbr' => array(),
            'fn_id_prefix' => '',
            'fn_link_title' => '',
            'fn_backlink_title' => '',
            'fn_link_class' => 'footnote-ref',
            'fn_backlink_class' => 'footnote-backref',
            'fn_backlink_html' => '&#8617;&#xFE0E;',
            'table_align_class_tmpl' => '',
            'code_class_prefix' => '',
            'code_attr_on_pre' => false,
            'enhanced_ordered_list' => true,
            'empty_element_suffix' => '>',
        ];
    }
}