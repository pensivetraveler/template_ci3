<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$platformName = $platformName??BUILDER_FLAGNAME;
?>

<!DOCTYPE html>
<?php include __DIR__.'/../../includes/head.php'; ?>
<!-- Error -->
<div class="misc-wrapper">
	<h1 class="mb-2 mx-2" style="font-size: 6rem; line-height: 6rem"><?=$status_code?></h1>
	<h4 class="mb-2">Page Not Found ⚠️</h4>
	<p class="mb-2 mx-2">we couldn't find the page you are looking for</p>
	<div class="d-flex justify-content-center mt-9">
		<img
			src="<?=base_url('public/assets/builder/img/illustrations/misc-error-object.png')?>"
			alt="misc-error"
			class="img-fluid misc-object d-none d-lg-inline-block"
			width="160" />
		<img
			src="<?=base_url('public/assets/builder/img/illustrations/misc-bg-light.png')?>"
			alt="misc-error"
			class="misc-bg d-none d-lg-inline-block"
			data-app-light-img="illustrations/misc-bg-light.png"
			data-app-dark-img="illustrations/misc-bg-dark.png" />
		<div class="d-flex flex-column align-items-center">
			<img
				src="<?=base_url('public/assets/builder/img/illustrations/misc-error-illustration.png')?>"
				alt="misc-error"
				class="img-fluid z-1"
				width="190" />
			<div>
				<a href="<?=base_url('admin')?>" class="btn btn-primary text-center my-10">Back to home</a>
			</div>
		</div>
	</div>
</div>
<!-- /Error -->
<?php
include __DIR__.'/../../includes/tail.php';
exit;
?>

