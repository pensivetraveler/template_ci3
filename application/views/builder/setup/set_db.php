<div class="position-relative vh-100 vw-100 d-flex justify-content-center align-items-center">
	<div class="w-px-600 m-auto">
		<div class="card p-2">
			<div class="card-header">
				<h5 class="mb-6 text-center"><?=lang('ADD DB SQL')?></h5>
			</div>
			<div class="card-body">
				<?php
				echo form_open_multipart('', [
					'id' => 'formAuth',
					'class' => "add-new-record needs-validation form-type-page",
					'action' => '',
					'method' => 'post'
				], [
					'_mode' => $this->router->method,
					'_event' => '',
				]);
				foreach ($formData as $item):
					?>
					<div class="row mb-4 form-validation-unit">
						<div class="col-sm-12">
							<div class="input-group input-group-merge">
								<?php
								echo form_textarea(
									[
										'name' => $item['field'],
										'id' => $item['id'],
										'rows' => 20,
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
