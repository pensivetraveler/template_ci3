<div class="col-sm-12 form-validation-unit">
	<?php
		$count = 1;
		foreach ($item['data'] as $fieldName=>$fieldData) :
			if($fieldData['type'] === 'hidden') :
				echo form_input(
					[
						'type' => 'hidden',
						'name' => get_group_field_name($fieldData['attr'], $fieldData['group'], $fieldData['field']),
						'id' => get_group_field_id($fieldData['attr'], $fieldData['group'], $fieldData['field']),
					],
					set_admin_form_value($fieldData['field'], $fieldData['default'], null),
					$fieldData['attributes'],
				);
			else :
				if($fieldData['type'] === 'custom'):
					builder_view("{$platformName}/layout/form_{$formType}_custom_".$fieldData['view'], ['item' => $fieldData]);
				else:
	?>
	<div class="input-group input-group-merge mb-3">
		<?=get_admin_form_ico($fieldData)?>
		<div class="form-floating form-floating-outline">
			<?=get_side_form_input_by_type($fieldData, 'side')?>
		</div>
	</div>
	<?=get_admin_form_text($fieldData)?>
	<?=get_admin_form_list_item($fieldData, 'side')?>
	<?php
				endif;
				$count++;
			endif;
		endforeach;
	?>
</div>
