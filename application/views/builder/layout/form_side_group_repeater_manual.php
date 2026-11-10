<?php
/**
 * 리피터 manual: jQuery 리피터 플러그인 없이 직접 추가·삭제 (group_attributes repeater_type = 'manual')
 * - 처음에는 repeater_count 개 행, name/id 는 get_group_field_name/id 규칙(행 번호 포함)
 * - <template data-manual-repeater-template> 의 __INDEX__ 를 새 행 번호로 바꿔 붙인다 (app-page-form.js)
 * - 삭제하면 남은 행 번호를 0부터 다시 매긴다
 */
?>
<div class="col-sm-12">
	<div
		class="p-3 border border-1 rounded-3 col-sm-12 border-input"
		data-form-type="side"
		data-group-name="<?=$item['group']?>"
		data-repeater-type="manual"
		data-repeater-count="<?=$item['attr']['repeater_count']?>"
		data-repeater-id="<?=$item['attr']['repeater_id']?>">
		<div class="row">
			<div class="col-sm-8 align-content-center text-primary">
				<span><?=lang($item['label'])?> <?=lang('Registration')?></span>
			</div>
			<div class="col-sm-4 text-end">
				<button class="btn btn-primary btn-sm" data-manual-repeater-add type="button">
					<i class="ri-add-line me-1"></i>
					<span class="align-middle"><?=lang('Add Row')?></span>
				</button>
			</div>
		</div>
		<div data-repeater-list="<?=$item['group']?>">
			<?php for($i = 0; $i < max(1, (int)$item['attr']['repeater_count']); $i++): ?>
			<div data-repeater-item data-row-index="<?=$i?>">
				<hr class="" />
				<div class="text-end">
					<button class="btn btn-outline-danger btn-sm p-1" data-manual-repeater-delete type="button">
						<i class="ri-close-line"></i>
					</button>
				</div>
				<div class="col-sm-12">
					<?php
						foreach ($item['data'] as $fieldName=>$fieldData) :
							// 행마다 group[i][field] (envelope 아니면 field[i]) name/id
							$fieldData['name'] = get_group_field_name($fieldData['group_attributes'], $fieldData['group'], $fieldData['field'], $i);
							$fieldData['id'] = get_group_field_id($fieldData['group_attributes'], $fieldData['group'], $fieldData['field'], $i);
							if($fieldData['type'] === 'hidden') :
								echo form_input(
									[
										'type' => 'hidden',
										'name' => $fieldData['name'],
										'id' => $fieldData['id'],
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
													'name' => $fieldData['name'],
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
												$fieldData['name'],
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
													'name' => $fieldData['name'],
													'id' => $fieldData['id'],
													'rows' => $fieldData['attributes']['rows']
												],
												set_admin_form_value($fieldData['field'], $fieldData['default'], null),
												$fieldData['attributes']
											);
											break;
										case 'file' :
											echo form_upload([
												'name' => $fieldData['name'],
												'id' => $fieldData['id'],
											], $fieldData['attributes']);
											break;
										default :
											echo form_input(
												[
													'type' => $fieldData['type'],
													'name' => $fieldData['name'],
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
		<template data-manual-repeater-template>
			<?php $i = '__INDEX__'; ?>
			<div data-repeater-item data-row-index="<?=$i?>">
				<hr class="" />
				<div class="text-end">
					<button class="btn btn-outline-danger btn-sm p-1" data-manual-repeater-delete type="button">
						<i class="ri-close-line"></i>
					</button>
				</div>
				<div class="col-sm-12">
					<?php
						foreach ($item['data'] as $fieldName=>$fieldData) :
							// 행마다 group[i][field] (envelope 아니면 field[i]) name/id
							$fieldData['name'] = get_group_field_name($fieldData['group_attributes'], $fieldData['group'], $fieldData['field'], $i);
							$fieldData['id'] = get_group_field_id($fieldData['group_attributes'], $fieldData['group'], $fieldData['field'], $i);
							if($fieldData['type'] === 'hidden') :
								echo form_input(
									[
										'type' => 'hidden',
										'name' => $fieldData['name'],
										'id' => $fieldData['id'],
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
													'name' => $fieldData['name'],
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
												$fieldData['name'],
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
													'name' => $fieldData['name'],
													'id' => $fieldData['id'],
													'rows' => $fieldData['attributes']['rows']
												],
												set_admin_form_value($fieldData['field'], $fieldData['default'], null),
												$fieldData['attributes']
											);
											break;
										case 'file' :
											echo form_upload([
												'name' => $fieldData['name'],
												'id' => $fieldData['id'],
											], $fieldData['attributes']);
											break;
										default :
											echo form_input(
												[
													'type' => $fieldData['type'],
													'name' => $fieldData['name'],
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
		</template>
	</div>
</div>
