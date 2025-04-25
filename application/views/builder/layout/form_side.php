<?php
	echo form_open_multipart('', [
		'id' => 'formRecord',
		'name' => 'formRecord',
		'class' => "add-new-record pt-0 row g-3 needs-validation form-type-{$formType}",
		'onsubmit' => 'return false',
	], [
		'_mode' => '',
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
<div class="col-sm-12 form-validation-unit">
	<div class="input-group input-group-merge">
		<?=get_admin_form_ico($item)?>
		<div class="form-floating form-floating-outline">
			<?=get_side_form_input_by_type($item, 'side')?>
		</div>
		<?php
			if($item['subtype'] === 'unique')
				echo form_button([
					'data-rel-field' => $item['field'],
					'type' => 'button',
					'class' => 'btn btn-outline-primary waves-effect btn-dup-check',
				], lang('Check'), [
					'onclick' => "checkDuplicate(this)",
					'disabled' => "disabled",
				]);
		?>
	</div>
	<?=get_admin_form_text($item)?>
	<?=get_admin_form_list_item($item, $formType)?>
</div>
<?php
		endif;
	endforeach;
?>
<div class="col-sm-12">
	<button type="submit" class="btn btn-primary data-submit me-sm-4 me-1"><?=lang('Submit')?></button>
	<button type="reset" class="btn btn-outline-secondary btn-cancel" data-bs-dismiss="offcanvas"><?=lang('Cancel')?></button>
	<?php if(in_array('delete', $actions)): ?>
	<button type="button" class="btn btn-outline-danger btn-delete d-none"><?=lang('Delete')?></button>
	<?php endif; ?>
	<!--<button type="button" class="btn btn-outline-danger" onclick="sampling()">Sampling</button>-->
</div>
<?=form_close()?>
