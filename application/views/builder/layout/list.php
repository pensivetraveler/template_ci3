<div class="row g-6 mb-6">
	<nav aria-label="breadcrumb">
		<ol class="breadcrumb breadcrumb-custom-icon"><?=get_breadcrumbs($titleList)?></ol>
	</nav>
</div>
<div class="row g-6 mb-6">
	<div class="col-sm-12">
		<div class="card">
			<?php builder_view("{$platformName}/layout/filter") ?>

			<div class="card-datatable table-responsive">
				<table class="datatables-records table">
					<thead>
						<tr>
							<th></th>
							<?php if($isCheckbox): ?>
							<th></th>
							<?php endif; ?>
							<?php foreach ($columns as $column): ?>
							<th><?=lang($column['label'])?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
				</table>
			</div>

			<?php if($formExist): ?>
			<!-- Modal to add new record -->
			<div
					class="offcanvas offcanvas-end"
					tabindex="-1"
					id="offcanvasRecord"
					data-form-style="<?=$formStyle?>"
					data-bs-scroll="true"
					data-bs-backdrop="true"
					data-bs-keyboard="false"
					aria-labelledby="offcanvasLabel">
				<div class="offcanvas-header border-bottom">
					<h5 class="offcanvas-title" id="offcanvasLabel"><?=lang('Add Record')?></h5>
					<button
							inert
							type="button"
							class="btn-close text-reset"
							data-bs-dismiss="offcanvas"
							aria-label="Close"></button>
				</div>
				<div class="offcanvas-body flex-grow-1">
					<?php builder_view("{$platformName}/layout/form_side", ['formData' => $formData, 'formType' => 'side', 'formSubType' => $formSubType]); ?>
				</div>
			</div>
			<!--/ Modal to add new record -->
			<?php endif; ?>

		</div>
	</div>
</div>
