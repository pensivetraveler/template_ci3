<div
	class="col-sm-12 form-validation-unit dropzone-wrapper"
	id="<?=$item['field']?>-dropzone-wrapper"
	data-field="<?=$item['field']?>"
	<?= set_dropzone_attributes($item['attributes']); ?>
>
	<input type="hidden" name="<?=$item['field']?>" value="">
    <div class="input-group input-group-merge">
        <div class="form-floating form-floating-outline">
            <div class="w-100 border border-input rounded-3 p-4 dropzone-file-container" data-field="<?=$item['field']?>">
                <div class="dz-message needsclick"><?=get_admin_form_text($item)?></div>
                <div class="fallback"><?=get_side_form_input_by_type($item, 'side')?></div>
            </div>
            <?=form_label(lang($item['label']),"{$item['field']}-dropzone-wrapper");?>
        </div>
    </div>
</div>
