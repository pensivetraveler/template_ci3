<div class="position-relative vh-100 vw-100 d-flex justify-content-center align-items-center">
	<div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
		<div class="authentication-inner py-6">

<!--		<div class="w-px-600 m-auto">-->
			<div class="card p-md-7 p-1">
<!--				<div class="card-header">-->
<!--				</div>-->
				<div class="card-body">
					<p class="mb-5 text-center"><?=lang('System Administrator Registration')?></p>
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
						echo form_hidden('redirect_to', $this->baseUri);
						foreach ($formData['fields'] as $item):
					?>
					<div class="row mb-4 form-validation-unit">
						<div class="col-sm-12">
							<div class="input-group input-group-merge">
								<div class="form-floating form-floating-outline">
									<?php
	//									echo get_admin_form_ico($item);
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
									<?=form_label(ucfirst($item['label']), $item['id'], ['class' => 'col-form-label text-primary'])?>
								</div>
							</div>
							<?=get_admin_form_text($item)?>
						</div>
					</div>
					<?php
						endforeach;
					?>
					<div class="row">
						<div class="col-sm-12 text-end">
							<button type="submit" class="btn btn-primary waves-effect waves-light d-grid w-100"><?=lang('Submit')?></button>
						</div>
					</div>
					<?=form_close();?>
				</div>
			</div>
		</div>
	</div>
</div>
