<?php extract($item['data']); ?>
<div class="col-sm-12 form-validation-unit">
	<div class="input-group flex-nowrap">
		<?=get_admin_form_ico($password)?>
		<div class="input-group input-group-merge form-password-toggle">
			<div class="form-floating form-floating-outline">
				<?php
					echo form_password([
						'name' => $password['field'],
						'id' => $password['id'],
					], $password['default'], $password['attributes']);
				?>
				<?php echo form_label(lang($password['label']), $password['id']); ?>
			</div>
			<span class="input-group-text cursor-pointer text-primary">
				<i class="password-toggle-icon ri-eye-off-line"></i>
			</span>
		</div>
	</div>
	<?=get_admin_form_text($password)?>
</div>
<div class="col-sm-12 form-validation-unit">
	<div class="input-group flex-nowrap">
		<?=get_admin_form_ico($password_confirm)?>
		<div class="input-group input-group-merge form-password-toggle">
			<div class="form-floating form-floating-outline">
				<?php
					echo form_password([
						'name' => $password_confirm['field'],
						'id' => $password_confirm['id'],
					], $password_confirm['default'], $password_confirm['attributes']);
				?>
				<?php echo form_label(lang($password_confirm['label']), $password_confirm['id']); ?>
			</div>
			<span class="input-group-text cursor-pointer text-primary">
				<i class="password-toggle-icon ri-eye-off-line"></i>
			</span>
		</div>
	</div>
	<?=get_admin_form_text($password_confirm)?>
</div>
