<?php
extract(trans_formdata_dit_type($viewData));
?>
<div class="col-12 mb-lg-0 mb-6">
	<div class="card">
		<?php
		echo form_open('', [
				'id' => 'formRecord',
				'class' => "view-record",
				'onsubmit' => 'return false',
		], [
				'_mode' => 'view',
				'_event' => '',
		]);
		echo form_input(
				[
						'type' => $identifier['type'],
						'name' => $identifier['field'],
						'id' => $identifier['id'],
				],
				set_admin_form_value($identifier['field'], $identifier['default'], null),
				$identifier['attributes'],
		);
		?>
		<div class="card-header">
			<div class="d-flex align-items-center justify-content-between">
				<h5 id="subject" class="mb-0 w-100 view-data"><span></span></h5>
				<div class="dropdown">
					<button
							class="btn btn-text-secondary rounded-pill text-muted border-0 p-1"
							type="button"
							id="actionBtns"
							data-bs-toggle="dropdown"
							aria-haspopup="true"
							aria-expanded="false">
						<i class="ri-more-2-line ri-20px"></i>
					</button>
					<div class="dropdown-menu dropdown-menu-end" aria-labelledby="actionBtns">
						<?php foreach ($actions as $action): ?>
							<a class="dropdown-item text-center btn-view-<?=$action?>" href="javascript:void(0);"><?=lang(ucfirst($action))?></a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<div class="d-flex justify-content-between align-items-center">
				<div class="d-flex align-items-center text-muted">
					<i class="ri-account-circle-line me-1"></i>
					<span id="created_id" class="fw-medium text-heading view-data"></span>
				</div>
				<div class="d-flex align-items-center text-muted">
					<i class="ri-time-line me-1"></i>
					<span id="created_dt" class="fw-medium me-2 view-data"><?=date('Y-m-d')?></span>
					<i class="ri-eye-line me-1"></i>
					<span id="view_count" class="fw-medium view-data">00</span>
				</div>
			</div>
		</div>
		<div class="card-body">
			<div class="row">
				<?php if(!empty($thumbnail)): ?>
					<div class="col-lg-4 col-sm-12">
						<div class="position-relative border rounded-2 h-px-400">
							<div id="thumbnail" class="w-100 h-100 overflow-hidden"></div>
							<p class="no-thumbnail-text">
								<span><?=lang('No Registered Thumbnail')?></span>
							</p>
						</div>
					</div>
					<div class="col-lg-8 col-sm-12">
						<div class="mt-4 mt-lg-0 border rounded-4 p-4">
							<div id="content" class="view-data"></div>
							<?php if(!empty($uploads)): ?>
								<hr>
								<ul id="uploads" class="view-data mb-0 rounded-3 mw-100 list-group list-group-flush bg-lighter p-2 mt-4 d-none"></ul>
							<?php endif; ?>
						</div>
					</div>
				<?php else: ?>
					<div class="col-lg-12 col-sm-12">
						<div class="mt-4 mt-lg-0 border rounded-4 p-4">
							<div id="content" class="view-data"></div>
							<?php if(!empty($uploads)): ?>
								<hr>
								<ul id="uploads" class="view-data mb-0 rounded-3 mw-100 list-group list-group-flush bg-lighter p-2 mt-4 d-none"></ul>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		echo form_close();
		?>
	</div>
	<?php if($isComments) builder_view("$platformName/layout/view_comments"); ?>
</div>
