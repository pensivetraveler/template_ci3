<?php
defined('BASEPATH') or exit('No direct script access allowed');

$config['web_list_config_loaded'] = true;

$config['list_myworks_config'] = [
	[
		'field' => 'subject',
		'label' => 'lang:article.subject',
		'onclick' => [
			'kind' => 'view',
		],
	],
	[
		'field' => 'open_yn',
		'label' => 'lang:article.open_yn',
	],
	[
		'field' => 'created_id',
		'label' => 'lang:common.created_id',
	],
	[
		'field' => 'recent_dt',
		'label' => 'lang:common.recent_dt',
	],
	[
		'field' => 'view_count',
		'label' => 'lang:article.view_count',
	],
];

$config['list_works_config'] = [
	[
		'field' => 'subject',
		'label' => 'lang:article.subject',
		'onclick' => [
			'kind' => 'view',
		],
	],
	[
		'field' => 'created_id',
		'label' => 'lang:common.created_id',
	],
	[
		'field' => 'recent_dt',
		'label' => 'lang:common.recent_dt',
	],
	[
		'field' => 'view_count',
		'label' => 'lang:article.view_count',
	],
];

$config['list_notices_config'] = [
	[
		'field' => 'subject',
		'label' => 'lang:article.subject',
		'onclick' => [
			'kind' => 'view',
		],
	],
	[
		'field' => 'created_id',
		'label' => 'lang:common.created_id',
	],
	[
		'field' => 'recent_dt',
		'label' => 'lang:common.recent_dt',
	],
	[
		'field' => 'view_count',
		'label' => 'lang:article.view_count',
	],
];

$config['list_inquiries_config'] = [
	[
		'field' => 'subject',
		'label' => 'lang:article.subject',
		'onclick' => [
			'kind' => 'view',
		],
	],
	[
		'field' => 'created_id',
		'label' => 'lang:common.created_id',
	],
	[
		'field' => 'recent_dt',
		'label' => 'lang:common.recent_dt',
	],
	[
		'field' => 'reply_yn',
		'label' => 'lang:article.reply_yn',
	],
];

