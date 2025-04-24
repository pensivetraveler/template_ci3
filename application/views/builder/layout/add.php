<!-- Basic Layout -->
<div class="row g-6 mb-6">
	<nav aria-label="breadcrumb">
		<ol class="breadcrumb"><?=get_admin_breadcrumbs($titleList)?></ol>
	</nav>
</div>
<div class="row g-6 mb-6">
	<div class="card mb-6">
		<div class="card-header d-flex justify-content-between align-items-center">
			<h5 class="mb-0"><?=lang('nav.'.$titleList[1])?> <?=lang('Registration')?></h5>
		</div>
		<div class="card-body">
			<?php builder_view("{$platformName}/layout/form_page", ['formType' => 'page', 'formData' => $formData]); ?>
		</div>
	</div>
</div>
