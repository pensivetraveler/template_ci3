<div class="col-sm-12 form-validation-unit">
	<div class="input-group input-group-merge">
		<?=get_admin_form_ico($item)?>
		<div class="form-floating form-floating-outline">
			<?php
				echo form_input([
					'type' => 'url',
					'name' => $item['field'],
					'id' => $item['id'],
				], $item['default'], $item['attributes']);
			?>
			<?=form_label(lang($item['label']), $item['id'])?>
		</div>
	</div>
	<?=get_admin_form_text($item)?>
	<div class="input-group input-group-merge mt-3">
		<div class="form-floating form-floating-outline">
			<ul class="list-unstyled m-0 p-2 bg-lighter rounded-3 d-none" id="<?=$item['id']?>-list">
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
</div>
