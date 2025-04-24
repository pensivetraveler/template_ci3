<?php
if(!isset($includes)) $includes = $this->config->config['base_includes_config'];
echo doctype('html5');
if($includes['head']) builder_view("{$platformName}/includes/head");
if($includes['header']) builder_view("{$platformName}/includes/header");
if($includes['modalPrepend']) builder_view("{$platformName}/includes/modal_prepend");
if(isset($subPage)) builder_view($subPage);
if($includes['modalAppend']) builder_view("{$platformName}/includes/modal_append");
if($includes['footer']) builder_view("{$platformName}/includes/footer");
if($includes['tail']) builder_view("{$platformName}/includes/tail");
//exit;
