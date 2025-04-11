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
							<img src="//placehold.co/400x200?text=Logo" alt="">
						</span>
					</a>
				</div>
				<!-- /Logo -->

				<div class="card-body mt-1">
					<p class="mb-5 text-center"><?=lang('Find ID')?></p>
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
										'type' => $email['type'],
										'name' => $email['field'],
										'id' => $email['id'],
									],
									set_admin_form_value($email['field'], $email['default'], null),
									$email['attributes']
								);
								echo form_label(lang($email['label']), $email['id']);
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
												'type' => $tel['type'],
												'name' => $tel['field'],
												'id' => $tel['id'],
										],
										set_admin_form_value($tel['field'], $tel['default'], null),
										$tel['attributes']
								);
								echo form_label(lang($tel['label']), $tel['id']);
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
								<span class="d-inline-block mb-1 mt-2 text-primary">&nbsp;|&nbsp;</span>
								<a href="<?=base_url('/admin/auth/findPassword')?>" class="float-end mb-1 mt-2">
									<span><?=lang('Find Password')?></span>
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
