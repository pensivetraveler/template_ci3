<?php
    echo form_open_multipart('', [
        'id' => 'formRecord',
        'class' => "add-new-record needs-validation form-type-{$formType}",
        'onsubmit' => 'return false',
    ], [
        '_mode' => $this->router->method,
        '_event' => '',
    ]);
	foreach ($formData as $item):
		if($item['category'] === 'group'):
			builder_view("$platformName/layout/form_{$formType}_group_".$item['view'], ['item' => $item]);
		elseif($item['category'] === 'custom'):
			builder_view("$platformName/layout/form_{$formType}_custom_".$item['view'], ['item' => $item]);
		elseif($item['type'] === 'hidden'):
			echo form_input(
				[
					'type' => $item['type'],
					'name' => $item['field'],
					'id' => $item['id'],
				],
				set_admin_form_value($item['field'], $item['default'], null),
				$item['attributes'],
			);
		else:
?>
<div class="row mb-4 form-validation-unit">
	<?=form_label(lang($item['label']), $item['id'], ['class' => 'col-sm-2 col-form-label'])?>
	<div class="col-sm-10">
		<div class="input-group input-group-merge">
            <?php
				echo get_admin_form_ico($item);
                switch ($item['type']) {
                    case 'password' :
                        echo form_password(
							[
								'name' => $item['field'],
								'id' => $item['id'],
							],
							set_admin_form_value($item['field'], $item['default'], null),
							$item['attributes']
						);
                        break;
					case 'checkbox' :
					case 'radio' :
						echo get_admin_form_choice($item, $formType);
						break;
                    case 'select' :
                        echo form_dropdown(
                            $item['field'],
                            $item['options'] ?? [],
							set_admin_form_value($item['field'], $item['default'], null),
                            array_merge([
                                'id' => $item['id'],
                                'data-style' => 'btn-default'
                            ], $item['attributes'])
                        );
                        break;
                    case 'textarea' :
                        echo form_textarea(
							[
								'name' => $item['field'],
								'id' => $item['id'],
								'rows' => $item['attributes']['rows']
							],
							set_admin_form_value($item['field'], $item['default'], null),
							$item['attributes']
						);
                        break;
					case 'file' :
						echo form_upload([
							'name' => $item['field'],
							'id' => $item['id'],
						], $item['attributes']);
						break;
                    default :
                        echo form_input(
							[
								'type' => $item['type'],
								'name' => $item['field'],
								'id' => $item['id'],
							],
							set_admin_form_value($item['field'], $item['default'], null),
							$item['attributes']
						);
                }
            ?>
			<?php
				if($item['form_attributes']['with_btn']) {
					switch ($item['form_attributes']['btn_type']) {
						case 'dup_check' :
							echo form_button([
								'data-rel-field' => $item['field'],
								'type' => 'button',
								'class' => 'btn btn-outline-primary waves-effect btn-dup-check',
							], lang('Check'), [
								'onclick' => "checkDuplicate(this)",
								'disabled' => 'disabled',
							]);
							break;
					}
				}
			?>
		</div>
		<?=get_admin_form_text($item)?>
		<?=get_admin_form_list_item($item, $formType)?>
	</div>
</div>
<?php
        endif;
    endforeach;
?>
<div class="row">
	<div class="col-sm-12 text-end">
		<button type="button" class="btn btn-outline-dark waves-effect" onclick="<?=WEB_HISTORY_BACK?>"><?=lang('List')?></button>
		<button type="submit" class="btn btn-primary waves-effect waves-light"><?=lang('Submit')?></button>
		<button type="button" class="btn btn-outline-danger btn-delete-event btn-delete d-none"><?=lang('Delete')?></button>
	</div>
</div>
<?=form_close();?>
