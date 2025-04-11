<?php
if (!defined("BASEPATH")) exit("No direct script access allowed");

class Form_builder
{
    public string $formType;
    public string $validationRowSelector;

    function __construct()
    {

    }

    public function load($formType, $validationRowSelector)
    {
        $this->formType = $formType;
        $this->validationRowSelector = $validationRowSelector;
    }

    public function row($item): string
    {
        $output  = $this->_prepend_row($item);
        $output .= $this->_get_row($item);
        $output .= $this->_append_row($item);
        return $output;
    }

    public function run($formData, $formType = '', $rowSelector = ''): string
    {
        if($formType) $this->formType = $formType;
        if($rowSelector) $this->validationRowSelector = $rowSelector;

        $form = $this->_get_form($formData);
        $inner = '';
        foreach ($formData as $item):
            $inner .= $this->row($item);
        endforeach;

        $output  = $this->_prepend_form();
        $output .= insert_html_inside_tag($form, $inner);
        $output .= $this->_append_form();

        return $output;
    }

    protected function _get_form($formData): string
    {
        $output  = form_open();
        $output .= form_close();
        return $output;
    }

    protected function _get_row($item): string
    {
        $output  = $this->_prepend_wrap($item);
        $output .= $this->_get_wrap($item);
        $output .= $this->_append_wrap($item);

        $output = convert_selector_to_html('div.row.mb-4', true, $output);
        return modify_html_attributes($output, [
            'class' => $this->validationRowSelector,
        ], 'append');
    }

    protected function _get_wrap($item): string
    {
        $output  = $this->_prepare_input($item);
        $output .= $this->_get_input($item);
        $output .= $this->_append_input($item);

        $output = convert_selector_to_html('div.input-group.input-group-merge', true, $output);
        return convert_selector_to_html('div.col-sm-10', true, $output);
    }

    protected function _get_input($item): string
    {
        return form_input();
    }

    protected function _prepend_form(): string
    {
        return '';
    }

    protected function _prepend_row($item): string
    {
        return '';
    }

    protected function _prepend_wrap($item): string
    {
        return '';
    }

    protected function _prepend_input($item): string
    {
        return form_label();
    }

    protected function _append_input($item): string
    {
        return '';
    }

    protected function _append_wrap($item): string
    {
        return '';
    }

    protected function _append_row($item): string
    {
        return '';
    }

    protected function _append_form(): string
    {
        return '';
    }
}