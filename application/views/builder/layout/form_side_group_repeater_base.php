<div class="col-sm-12">
	<div
		class="p-3 border border-1 rounded-3 col-sm-12 border-input"
		data-form-type="side"
		data-group-name="<?=$item['group']?>"
		data-repeater-type="<?=$item['attr']['repeater_type']?>"
		data-repeater-count="<?=$item['attr']['repeater_count']?>"
		data-repeater-id="<?=$item['attr']['repeater_id']?>">
		<div class="row">
			<div class="col-sm-12 align-content-center text-primary">
				<span><?=lang($item['label'])?> <?=lang('Registration')?></span>
			</div>
		</div>
		<div data-repeater-list="<?=$item['group']?>">
			<?php for($i = 0; $i < $item['attr']['repeater_count']; $i++): ?>
			<div data-repeater-item data-row-index="1">
				<hr class="" />
				<div class="col-sm-12">
					<?php
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
					<div class="form-validation-unit">
						<div class="input-group input-group-merge">
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
						</div>
						<?=get_admin_form_text($fieldData)?>
						<?=get_admin_form_list_item($fieldData, 'side')?>
					</div>
					<?php
							endif;
						endforeach;
					?>
				</div>
			</div>
			<?php endfor; ?>
		</div>
	</div>
</div>
