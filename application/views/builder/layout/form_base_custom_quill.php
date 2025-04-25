<div class="row mb-3 form-validation-unit">
	<?=get_builder_form_label($item, ['class' => 'col-sm-2 col-form-label'])?>
	<div class="col-sm-10">
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
</div>
