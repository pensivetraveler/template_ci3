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

	foreach ($formData['fields'] as $row) :
?>
<div class="row">
	<?php
		foreach ($row as $item):
			if($item['type'] === 'common'):
				echo "<div class='col-md-{$item['colspan']} d-sm-block d-none'></div>";
			elseif($item['category'] === 'group'):
				builder_view("{$platformName}/layout/form_{$formType}_group_".$item['view'], ['item' => $item]);
			elseif($item['category'] === 'custom'):
				builder_view("{$platformName}/layout/form_{$formType}_custom_".$item['view'], ['item' => $item]);
			else:
	?>
	<div class="col-md-<?=$item['colspan']?> mb-6 form-validation-unit" data-field-name="<?=$item['field']??''?>">
		<?=get_builder_form_label($item, ['class' => 'd-block col-form-label fs-6 text-primary py-0 mb-2 fw-bolder'])?>
		<div class="input-group input-group-merge">
			<?php
			echo get_admin_form_ico($item);
			echo get_page_form_input_by_type($item, 'page');
			?>
		</div>
		<?=get_admin_form_text($item)?>
		<?=get_admin_form_list_item($item, $formType)?>
	</div>
	<?php
			endif;
		endforeach;
	?>
</div>
<?php
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
