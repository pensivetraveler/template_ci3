<div class="row mb-3 form-validation-unit">
	<?=get_builder_form_label($item, ['class' => 'col-sm-2 col-form-label'])?>
	<div class="col-sm-10">
		<div class="input-group input-group-merge">
			<?=get_admin_form_ico($item)?>
			<?php
				echo form_input([
					'name' => $item['field'],
					'id' => $item['id'],
				], $item['default'], $item['attributes']);
			?>
		</div>
		<?=get_admin_form_text($item)?>
	</div>
</div>
