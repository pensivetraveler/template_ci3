<?php
// 파일 하나(썸네일 등)용 드롭존 (페이지 grid 폼). 설정: type custom, subtype dropzone-single.
// 동작은 base 폼과 같다 (form_base_custom_dropzone-single.php)
$item['attributes']['max'] = 1;
?>
<div class="col-md-<?=$item['colspan']?> mb-6 form-validation-unit" data-field-name="<?=$item['field']??''?>" id="<?=$item['field']?>-dropzone-wrapper">
	<?=get_builder_form_label($item, ['class' => 'd-block col-form-label fs-6 text-primary py-0 mb-2 fw-bolder'])?>
	<div class="w-px-400 mw-100 border border-input rounded-3 p-4 dropzone-wrapper" id="<?=$item['id']?>-dropzone" data-field="<?=$item['field']?>" <?=set_dropzone_attributes($item['attributes'])?>>
		<div class="dz-message needsclick m-0">
			<span class="text-primary"><?=lang('Drop file here or click to upload')?></span>
		</div>
		<div class="dropzone-file-container"></div>
		<div class="fallback">
			<?=form_upload(['name' => $item['field'], 'id' => $item['id']], $item['attributes'])?>
		</div>
	</div>
	<?=get_admin_form_text($item)?>
</div>
