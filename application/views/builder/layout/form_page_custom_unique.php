<div class="row mb-4 form-validation-unit">
	<?=form_label(lang($item['label']), $item['id'], ['class' => 'col-sm-2 col-form-label'])?>
	<div class="col-sm-10">
		<?php
			echo form_input(
				[
					'type' => 'hidden',
					'name' => $item['field'].'_unique',
					'id' => $item['id'].'_unique',
				]
			);
		?>
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
</div>
