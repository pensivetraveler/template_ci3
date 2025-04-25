<?php extract($item['data']); ?>
<div class="row mb-3 form-validation-unit">
	<?=get_builder_form_label($item, ['class' => 'col-sm-2 col-form-label'])?>
	<div class="col-sm-10">
		<div class="input-group input-group-merge mb-4">
			<span id="<?=$zipcode['field']?>-ico" class="input-group-text"><i class="ri-building-line ri-18px"></i></span>
			<div class="form-floating form-floating-outline">
				<?php
					echo form_input([
						'name' => $zipcode['field'],
						'id' => $zipcode['id'],
					], $zipcode['default'], $zipcode['attributes']);
					echo form_label(lang($zipcode['label']), $zipcode['id']);
				?>
			</div>
			<?=form_button([
				'id' => 'button-addr',
				'type' => 'button',
				'class' => 'btn btn-outline-primary waves-effect',
			], lang('Search'), [
				'onclick' => "findAddress(document.getElementById('daumAddrWrap_{$zipcode['group']}'))",
			])?>
		</div>
		<div class="input-group input-group-merge mb-4">
			<div class="form-floating form-floating-outline">
				<?php
					echo form_input([
						'name' => $addr1['field'],
						'id' => $addr1['id'],
					], $addr1['default'], $addr1['attributes']);
					echo form_label(lang($addr1['label']), $addr1['id']);
				?>
			</div>
		</div>
		<div class="input-group input-group-merge">
			<div class="form-floating form-floating-outline">
				<?php
					echo form_input([
						'name' => $addr2['field'],
						'id' => $addr2['id'],
					], $addr2['default'], $addr2['attributes']);
					echo form_label(lang($addr2['label']), $addr2['id']);
				?>
			</div>
		</div>
		<?=get_admin_form_text($item)?>
		<div class="daum-addr-container">
			<div id="daumAddrWrap_<?=$item['group']?>" data-group-name="<?=$item['group']?>">
				<img src="//t1.daumcdn.net/postcode/resource/images/close.png" id="btnFoldWrap" onclick="foldDaumPostcode(this.parentElement)" alt="접기 버튼">
			</div>
		</div>
		<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
	</div>
</div>
