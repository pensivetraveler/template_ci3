<?php
// 파일 하나(썸네일 등)용 드롭존 (페이지 base 폼). 설정: type custom, subtype dropzone-single.
// 고른 파일은 저장 때 getFormData() 가 Dropzone 인스턴스에서 {field} 로 넣는다(자동 업로드 안 함).
// 수정 화면은 기존 파일을 미리보기로 보여 주고, 지우면 deleteFile/{행 id}?type={field} 로 지운다 (applyFrmValues, setDropzone)
$item['attributes']['max'] = 1;
?>
<div class="row mb-3 form-validation-unit" id="<?=$item['field']?>-dropzone-wrapper">
	<?=get_builder_form_label($item, ['class' => 'col-sm-2 col-form-label text-primary fw-bolder'])?>
	<div class="col-sm-10">
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
</div>
