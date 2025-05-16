<?php extract($item['data']); ?>
<div class="col-md-<?=$password['colspan']??6?> mb-6 form-validation-unit" data-field-name="<?=$password['field']??''?>">
	<?=get_builder_form_label($password, ['class' => 'd-block col-form-label fs-6 text-primary py-0 mb-2 fw-bolder'])?>
	<div class="input-group input-group-merge form-password-toggle">
		<?php
			echo get_admin_form_ico($password);
			echo form_password([
				'name' => $password['field'],
				'id' => $password['id'],
			], $password['default'], $password['attributes']);
		?>
		<span class="input-group-text cursor-pointer text-primary">
			<i class="password-toggle-icon ri-eye-off-line"></i>
		</span>
	</div>
	<?=get_admin_form_text($password)?>
</div>
<div class="col-md-<?=$password_confirm['colspan']??6?> mb-6 form-validation-unit" data-field-name="<?=$password_confirm['field']??''?>">
	<?=get_builder_form_label($password_confirm, ['class' => 'd-block col-form-label fs-6 text-primary py-0 mb-2 fw-bolder'])?>
	<div class="input-group input-group-merge form-password-toggle">
		<?php
			echo get_admin_form_ico($password_confirm);
			echo form_password([
				'name' => $password_confirm['field'],
				'id' => $password_confirm['id'],
			], $password_confirm['default'], $password_confirm['attributes']);
		?>
		<span class="input-group-text cursor-pointer text-primary">
			<i class="password-toggle-icon ri-eye-off-line"></i>
		</span>
	</div>
	<?=get_admin_form_text($password_confirm)?>
</div>