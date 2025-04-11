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

	<script>
		<?=$this->config->config['phptojs']['namespace']?>.ERRORS.push({
			type : 'exception',
			views : <?=json_encode(config_item('loaded_views'))?>,
			static : <?=(int)!$this->postController?>,
			summary : {
				type : '<?=get_class($exception)?>',
				lifeCycle : '<?=config_item('life_cycle')?>',
				message : '<?=addslashes($message)?>',
				filename : '<?=$exception->getFile()?>',
				lineNumber : '<?=$exception->getLine()?>',
			},
			backtrace : [
				<?php
				if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE):
				foreach (debug_backtrace() as $error):
				if (isset($error['file']) && strpos($error['file'], realpath(BASEPATH)) !== 0):
				?>
				{
					file : '<?=$error['file']?>',
					line : '<?=$error['line']?>',
					func : '<?=$error['function']?>',
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
