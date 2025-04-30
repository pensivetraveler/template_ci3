<div class="position-relative vh-100 vw-100 d-flex justify-content-center align-items-center">
	<div class="w-px-600 m-auto">
		<div class="card p-2">
			<div class="card-header">
				<h5 class="mb-6 text-center"><?=lang('System Administrator Registration')?></h5>
			</div>
			<div class="card-body">
				<?php
					echo form_open_multipart('', [
							'id' => 'formAuth',
							'class' => "add-new-record needs-validation form-type-page",
							'method' => 'post',
							'action' => '',
					], [
							'_mode' => $this->router->method,
							'_event' => '',
					]);
					foreach ($formData['fields'] as $item):
				?>
				<div class="row mb-4 form-validation-unit">
					<?=form_label(ucfirst($item['label']), $item['id'], ['class' => 'col-sm-2 col-form-label'])?>
					<div class="col-sm-10">
						<div class="input-group input-group-merge">
							<?php
								echo get_admin_form_ico($item);
								echo form_input(
									[
										'type' => $item['type'],
										'name' => $item['field'],
										'id' => $item['id'],
									],
									set_admin_form_value($item['field'], $item['default'], null),
									$item['attributes']
								);
							?>
						</div>
						<?=get_admin_form_text($item)?>
					</div>
				</div>
				<?php
					endforeach;
				?>
				<div class="row">
					<div class="col-sm-12 text-end">
						<button type="submit" class="btn btn-primary waves-effect waves-light"><?=lang('Submit')?></button>
					</div>
				</div>
				<?=form_close();?>
			</div>
		</div>
	</div>
</div>
