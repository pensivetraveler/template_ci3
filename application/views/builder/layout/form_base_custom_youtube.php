<div class="row mb-3 form-validation-unit">
	<?=form_label(lang($item['label']), $item['id'], ['class' => 'col-sm-2 col-form-label'])?>
	<div class="col-sm-10">
		<div class="input-group input-group-merge">
			<?=get_admin_form_ico($item)?>
			<?php
				echo form_input([
					'type' => 'url',
					'name' => $item['field'],
					'id' => $item['id'],
				], $item['default'], $item['attributes']);
			?>
		</div>
		<?=get_admin_form_text($item)?>
		<ul class="list-unstyled mb-3 mt-3 p-2 bg-lighter rounded-3 d-none" id="<?=$item['id']?>-list">
			<div class="d-flex align-items-center">
				<div class="badge text-body text-truncate">
					<a href="https://youtube.com" target="_blank">
						<i class="ri-link mt-0"></i>
						<span class="h6 mb-0">https://youtube.com</span>
					</a>
				</div>
			</div>
		</ul>
	</div>
</div>
