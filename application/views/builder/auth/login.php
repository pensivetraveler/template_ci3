<?php
extract(trans_formdata_dit_type($formData));
?>
<div class="position-relative">
	<div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
		<div class="authentication-inner py-6">
			<!-- Login -->
			<div class="card p-md-7 p-1">
				<!-- Logo -->
				<div class="app-brand justify-content-center mt-5">
					<a href="/admin" class="app-brand-link gap-2">
						<span class="app-brand-logo demo">
							<img src="//placehold.co/400x200?text=Logo" alt="">
						</span>
					</a>
				</div>
				<!-- /Logo -->

				<div class="card-body mt-1">
					<p class="mb-5 text-center"><?=lang('Login')?></p>
					<?php
						echo form_open('', [
							'id' => 'formAuth',
							'class' => "needs-validation form-type-page",
							'onsubmit' => 'return false',
						], []);
					?>
						<div class="col-sm-12 form-validation-unit">
							<div class="input-group input-group-merge">
								<div class="form-floating form-floating-outline">
									<?php
										echo form_input(
											[
												'type' => $id['type'],
												'name' => $id['field'],
												'id' => $id['id'],
											],
											set_admin_form_value($id['field'], $id['default'], null),
											$id['attributes']
										);
										echo form_label(lang($id['label']), $id['id']);
									?>
								</div>
							</div>
						</div>
						<div class="col-sm-12 form-validation-unit">
							<div class="input-group input-group-merge form-password-toggle">
								<div class="form-floating form-floating-outline">
									<?php
										echo form_password(
											[
												'name' => $password['field'],
												'id' => $password['id'],
											],
											set_admin_form_value($password['field'], $password['default'], null),
											$password['attributes']
										);
										echo form_label(lang($password['label']), $password['id']);
									?>
								</div>
								<span class="input-group-text cursor-pointer">
									<i class="ri-eye-off-line"></i>
								</span>
							</div>
						</div>
						<div class="col-sm-12 form-validation-unit">
							<div class="mb-5 d-flex justify-content-between mt-5">
								<div class="form-check mt-2">
									<?php
										echo form_checkbox([
											'name' => $autologin['field'],
											'id' => $autologin['id'],
											'class' => 'form-check-input',
										], '1', false, $autologin['attributes']);
										echo form_label(lang($autologin['label']), $autologin['id']);
									?>
								</div>
								<div>
									<a href="/builder/auth/findId" class="float-start mb-1 mt-2">
										<span><?=lang('Find ID')?></span>
									</a>
									<span class="d-inline-block mb-1 mt-2 text-primary">&nbsp;|&nbsp;</span>
									<a href="/builder/auth/findPassword" class="float-end mb-1 mt-2">
										<span><?=lang('Find Password')?></span>
									</a>
								</div>
							</div>
						</div>
						<div>
							<button class="btn btn-primary d-grid w-100" type="submit"><?=lang('Login')?></button>
						</div>
					<?php
						echo form_close();
					?>
				</div>
			</div>
			<!-- /Login -->
			<img
				alt="mask"
				src="<?=base_url('/public/assets/builder/img/illustrations/auth-basic-login-mask-light.png')?>"
				class="authentication-image d-none d-lg-block"
				data-app-light-img="illustrations/auth-basic-login-mask-light.png"
				data-app-dark-img="illustrations/auth-basic-login-mask-dark.png" />
		</div>
	</div>
</div>
