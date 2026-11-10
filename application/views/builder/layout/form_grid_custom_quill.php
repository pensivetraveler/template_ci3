<div class="col-md-<?=$item['colspan']?> mb-6 form-validation-unit" data-field-name="<?=$item['field']??''?>">
	<?=get_builder_form_label($item, ['class' => 'd-block col-form-label fs-6 text-primary py-0 mb-2 fw-bolder'])?>
	<?=form_input(
		[
			'type' => 'hidden',
			'name' => $item['field'],
			'id'   => $item['id'],
		],
		set_admin_form_value($item['field'], $item['default'], null),
		[
			'data-textarea-id' => "{$item['id']}-quill",
		]
	);?>
	<div id="<?=$item['id']?>-quill" class="textarea-quill ms-0">
		<?=set_admin_form_value($item['field'], $item['default'], null)?>
	</div>
	<?=get_admin_form_text($item)?>
</div>
