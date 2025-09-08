<div class="col-md-<?=$item['colspan']?> mb-6 form-validation-unit" data-field-name="<?=$item['field']??''?>">
	<?php
		echo form_input(
			[
				'type' => 'hidden',
				'name' => $item['field'].'_unique',
				'id' => $item['id'].'_unique',
			]
		);
	?>
	<?=get_builder_form_label($item, ['class' => 'd-block col-form-label fs-6 text-primary py-0 mb-2 fw-bolder'])?>
	<div class="input-group input-group-merge">
		<?php
			echo get_admin_form_ico($item);
			echo form_input(
				[
					'type' => $item['type'],
					'name' => $item['field'],
					'id' => $item['id'],
				],
				set_admin_form_value($item['field'], $item['default'], null),
				$item['attributes']
			);
			echo form_button([
				'data-rel-field' => $item['field'],
				'type' => 'button',
				'class' => 'btn btn-outline-primary waves-effect btn-dup-check',
			], lang('Check'), [
				'onclick' => "checkDuplicate(this)",
				'disabled' => 'disabled',
			]);

		?>
	</div>
	<?=get_admin_form_text($item)?>
</div>
