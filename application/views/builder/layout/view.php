<!-- Basic Layout -->
<div class="row g-6 mb-6">
	<nav aria-label="breadcrumb">
		<ol class="breadcrumb"><?=get_admin_breadcrumbs($titleList)?></ol>
	</nav>
</div>
<div class="row g-6 position-relative" id="view-container">
	<?php builder_view("$platformName/layout/view_{$viewType}"); ?>
	<div id="loader" class="loading w-100 position-absolute top-0">
		<div class="opacity-50 h-100 position-relative bg-lighter"></div>
		<div class="position-absolute translate-middle" style="top:50%;left:50%">
			<div class="sk-chase sk-primary">
				<div class="sk-chase-dot"></div>
				<div class="sk-chase-dot"></div>
				<div class="sk-chase-dot"></div>
				<div class="sk-chase-dot"></div>
				<div class="sk-chase-dot"></div>
				<div class="sk-chase-dot"></div>
			</div>
		</div>
	</div>
</div>
