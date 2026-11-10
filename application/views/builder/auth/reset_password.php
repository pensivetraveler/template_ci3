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
					<a href="/" class="app-brand-link gap-2">
						<span class="app-brand-logo demo">
							<img src="https://placehold.co/400x200?text=Logo" alt="">
						</span>
					</a>
				</div>
				<!-- /Logo -->

				<div class="card-body mt-1">
					<p class="mb-5 text-center"><?=lang('Reset Password')?></p>
					<?php
					echo form_open('', [
						'id' => 'formAuth',
						'class' => "needs-validation form-type-page",
						'data-no-submit' => 'true',
					], ['token' => $token]);
					?>
					<div class="col-sm-12 form-validation-unit">
						<div class="input-group input-group-merge form-password-toggle">
							<div class="form-floating form-floating-outline">
								<?php
								echo form_input(
									[
										'type' => $password['type'],
										'name' => $password['field'],
										'id' => $password['id'],
									],
									'',
									$password['attributes']
								);
								echo form_label(lang($password['label']), $password['id']);
								?>
							</div>
						</div>
					</div>
					<div class="col-sm-12 form-validation-unit">
						<div class="input-group input-group-merge form-password-toggle">
							<div class="form-floating form-floating-outline">
								<?php
								echo form_input(
									[
										'type' => $password_confirm['type'],
										'name' => $password_confirm['field'],
										'id' => $password_confirm['id'],
									],
									'',
									$password_confirm['attributes']
								);
								echo form_label(lang($password_confirm['label']), $password_confirm['id']);
								?>
							</div>
						</div>
					</div>
					<div>
						<button class="btn btn-primary d-grid w-100" type="submit"><?=lang('Submit')?></button>
					</div>
					<?php
					echo form_close();
					?>
					<hr>
					<div class="col-sm-12">
						<div class="d-flex justify-content-end">
							<div>
								<a href="<?=base_url('/admin/auth/login')?>" class="float-start mb-1 mt-2">
									<span><?=lang('Login')?></span>
								</a>
							</div>
						</div>
					</div>
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
