<?php
echo doctype('html5');
$this->load->view("web/includes/head");
$this->load->view($subPage);
$this->load->view("web/includes/tail");
//exit;
