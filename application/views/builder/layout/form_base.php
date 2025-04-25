<?php
    echo form_open_multipart('', [
        'id' => 'formRecord',
        'name' => 'formRecord',
        'class' => "add-new-record needs-validation form-type-page form-type-{$formType}",
        'onsubmit' => 'return false',
    ], [
        '_mode' => $this->router->method,
        '_event' => '',
    ]);

	foreach ($formData['hiddens'] as $item) :
		echo form_input(
			[
				'type' => $item['type'],
				'name' => $item['field'],
				'id' => $item['id'],
			],
			set_admin_form_value($item['field'], $item['default'], null),
			$item['attributes'],
		);
	endforeach;

    foreach ($formData['fields'] as $item):
        if($item['category'] === 'group'):
            builder_view("{$platformName}/layout/form_{$formType}_group_".$item['view'], ['item' => $item]);
        elseif($item['category'] === 'custom'):
            builder_view("{$platformName}/layout/form_{$formType}_custom_".$item['view'], ['item' => $item]);
        else:
?>
<div class="row mb-3 form-validation-unit">
	<?=get_builder_form_label($item, ['class' => 'col-sm-2 col-form-label text-primary fw-bolder'])?>
    <div class="col-sm-10">
        <div class="input-group input-group-merge">
            <?php
            echo get_admin_form_ico($item);
            echo get_page_form_input_by_type($item, 'page');
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
	<div class="col-sm-6 text-start">
		<?php foreach ($buttons as $button=>$attr): ?>
		<button type="button" class="btn btn-outline-dark waves-effect btn-view-<?=$button?>"><?=lang($attr['text'])?></button>
		<?php endforeach; ?>
	</div>
	<div class="col-sm-12 text-end">
		<?php if(in_array('list', $actions)): ?>
		<button type="button" class="btn btn-outline-dark waves-effect" onclick="<?=WEB_HISTORY_BACK?>"><?=lang('List')?></button>
		<?php endif; ?>
		<button type="submit" class="btn btn-primary waves-effect waves-light"><?=lang('Submit')?></button>
		<?php if(in_array('delete', $actions)): ?>
		<button type="button" class="btn btn-outline-danger btn-delete-event btn-delete d-none"><?=lang('Delete')?></button>
		<?php endif; ?>
	</div>
</div>
<?=form_close();?>
