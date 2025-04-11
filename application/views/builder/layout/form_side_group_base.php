<?php extract($item['data']); ?>
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
					'',
					$fieldData['attributes'],
				);
			else :
	?>
	<div class="input-group input-group-merge <?=$count === count($item['data'])?'mb-3':''?>">
		<?=get_admin_form_ico($fieldData)?>
		<div class="form-floating form-floating-outline">
			<?php
			switch ($fieldData['type']) {
				case 'password' :
					echo form_password(
						[
							'name' => $fieldData['field'],
							'id' => $fieldData['id'],
						],
						set_admin_form_value($fieldData['field'], $fieldData['default'], null),
						$fieldData['attributes']
					);
					break;
				case 'checkbox' :
				case 'radio' :
					echo get_admin_form_choice($fieldData, 'side');
					break;
				case 'select' :
					echo form_dropdown(
						$fieldData['field'],
						$fieldData['options'] ?? [],
						set_admin_form_value($fieldData['field'], $fieldData['default'], null),
						array_merge([
							'id' => $fieldData['id'],
							'data-style' => 'btn-default'
						], $fieldData['attributes'])
					);
					break;
				case 'textarea' :
					echo form_textarea(
						[
							'name' => $fieldData['field'],
							'id' => $fieldData['id'],
							'rows' => $fieldData['attributes']['rows']
						],
						set_admin_form_value($fieldData['field'], $fieldData['default'], null),
						$fieldData['attributes']
					);
					break;
				case 'file' :
					echo form_upload([
						'name' => $fieldData['field'],
						'id' => $fieldData['id'],
					], $fieldData['attributes']);
					break;
				default :
					echo form_input(
						[
							'type' => $fieldData['type'],
							'name' => $fieldData['field'],
							'id' => $fieldData['id'],
						],
						set_admin_form_value($fieldData['field'], $fieldData['default'], null),
						$fieldData['attributes']
					);
			}
			echo form_label(lang($fieldData['label']), $fieldData['id']);
			?>
		</div>
		<?=get_admin_form_text($fieldData)?>
		<?=get_admin_form_list_item($fieldData, 'side')?>
	</div>
	<?php
			endif;
			$count++;
		endforeach;
	?>
</div>
