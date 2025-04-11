<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$platformName = $platformName??BUILDER_FLAGNAME;
?>

<!DOCTYPE html>
<?php include VIEWPATH.$platformName.'/includes/head.php'; ?>
<!-- Server Error -->
<div class="misc-wrapper">
	<h1 class="mb-2 mx-2" style="font-size: 6rem; line-height: 6rem"><?=$status_code?></h1>
	<h4 class="mb-2"><?=$heading?></h4>
	<p class="mb-2 mx-2"><?=$message?></p>
	<div class="d-flex justify-content-center mt-12">
		<img
				src="<?=base_url('public/assets/builder/img/illustrations/misc-error-object.png')?>"
				alt="misc-server-error"
				class="img-fluid misc-object d-none d-lg-inline-block"
				width="160" />
		<img
				src="<?=base_url('public/assets/builder/img/illustrations/misc-bg-light.png')?>"
				alt="misc-server-error"
				class="misc-bg d-none d-lg-inline-block z-n1"
				data-app-light-img="illustrations/misc-bg-light.png"
				data-app-dark-img="illustrations/misc-bg-dark.png" />
		<div class="d-flex flex-column align-items-center">
			<img
					src="<?=base_url('public/assets/builder/img/illustrations/misc-server-error-illustration.png')?>"
					alt="misc-server-error"
					class="img-fluid z-1"
					width="230" />
			<div>
				<a href="<?=base_url('admin')?>" class="btn btn-primary text-center my-10">Back to home</a>
			</div>
		</div>
	</div>
</div>
<!-- /Server Error -->
<?php
include VIEWPATH.$platformName.'/includes/tail.php';
exit;
?>
