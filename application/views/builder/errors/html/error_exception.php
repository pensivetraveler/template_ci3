<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$platformName = $platformName??BUILDER_FLAGNAME;
?>

<?php
if(!in_array('head', config_item('loaded_views'))) {
	echo doctype('html5');
	builder_view("$platformName/includes/head");
}

if(!in_array('modal_prepend', config_item('loaded_views'))) {
	builder_view("$platformName/includes/modal_prepend");
}
?>

	<?php
	// 메시지에 요청 값이 섞일 수 있어 스크립트를 벗어나지 않게 JSON 으로 출력한다
	$toJs = function ($value) { return json_encode((string)$value, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_INVALID_UTF8_SUBSTITUTE); };
	?>
	<script>
		<?=$this->config->config['phptojs']['namespace']?>.ERRORS.push({
			type : 'exception',
			views : <?=json_encode(config_item('loaded_views'), JSON_HEX_TAG)?>,
			static : <?=(int)!$this->postController?>,
			summary : {
				type : <?=$toJs(get_class($exception))?>,
				lifeCycle : '<?=config_item('life_cycle')?>',
				message : <?=$toJs($message)?>,
				filename : <?=$toJs($exception->getFile())?>,
				lineNumber : <?=$toJs($exception->getLine())?>,
			},
			backtrace : [
				<?php
				if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE):
				foreach (debug_backtrace() as $error):
				if (isset($error['file']) && strpos($error['file'], realpath(BASEPATH)) !== 0):
				?>
				{
					file : <?=$toJs($error['file'])?>,
					line : <?=$toJs($error['line'])?>,
					func : <?=$toJs($error['function'])?>,
				},
				<?php
				endif;
				endforeach;
				endif;
				?>
			]
		});
	</script>

<?php
if(!in_array('tail', config_item('loaded_views'))) {
	builder_view("$platformName/includes/tail");
	exit;
}
