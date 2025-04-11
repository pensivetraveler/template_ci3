<div class="row g-6 mb-6">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb"><?=get_admin_breadcrumbs($titleList)?></ol>
    </nav>
</div>
<div class="row g-6 mb-6">
	<div class="col-sm-12">
		<div class="card">
			<?php if(isset($filters) && !empty($filters)): ?>
			<div class="card-header border-bottom">
				<?=form_open('', [
					'id' => 'formFilter',
					'name' => 'formFilter',
					'class' => 'form-type-filter'
				], [
					'_onloaded' => 0,
				]); ?>
					<h5 class="card-title mb-0"><?=lang('Filters');?></h5>
					<?php if(isset($filterHelpBlock) && !empty($filterHelpBlock)) echo get_help_block($filterHelpBlock); ?>
					<?php foreach ($filters as $row): ?>
					<div class="d-flex justify-content-lg-start align-items-center row gx-5 pt-4 gap-5 gap-md-0">
						<?php
							foreach ($row as $item):
								if($item['type'] === 'filter') {
									switch ($item['subtype']) {
										case 'space' :
						?>
						<div class="col-md-<?=$item['colspan']?> d-sm-block filter-space"></div>
						<?php
											break;
										case 'submit' :
						?>
						<div class="col-md-3 d-flex align-items-center justify-content-end filter-btns">
							<button class="btn btn-primary w-px-100 btn-search" type="button">검색</button>
							<button class="btn btn-outline-primary w-px-100 btn-reset" type="reset">초기화</button>
						</div>
						<?php
											break;
									}
								} else {
						?>
						<div class="col-md-<?=$item['colspan']?>">
							<div class="input-group input-group-merge">
								<div class="form-floating form-floating-outline">
									<?php
										switch ($item['type']) {
											case 'password' :
												echo form_password(
													[
														'name' => $item['name'],
														'id' => $item['id'],
													],
													set_admin_form_value($item['field'], $item['default'], null),
													$item['attributes']
												);
												break;
											case 'checkbox' :
												echo get_admin_form_choice($item, 'filter');
												break;
											case 'radio' :
												echo get_admin_form_radio($item, 'filter');
												break;
											case 'select' :
												echo form_dropdown(
													$item['name'],
													$item['options'] ?? [],
													set_admin_form_value($item['field'], $item['default'], null),
													array_merge([
														'id' => $item['id'],
														'data-style' => 'btn-default'
													], $item['attributes'])
												);
												break;
											case 'textarea' :
												echo form_textarea(
													[
														'name' => $item['name'],
														'id' => $item['id'],
														'rows' => $item['attributes']['rows']
													],
													set_admin_form_value($item['field'], $item['default'], null),
													$item['attributes']
												);
												break;
											case 'file' :
												echo form_upload([
													'name' => $item['name'],
													'id' => $item['id'],
												], $item['attributes']);
												break;
											default :
												echo form_input(
													[
														'type' => $item['type'],
														'name' => $item['name'],
														'id' => $item['id'],
													],
													set_admin_form_value($item['field'], $item['default'], null),
													$item['attributes']
												);
										}
										echo form_label(lang($item['label']), $item['id']);
									?>
								</div>
							</div>
						</div>
						<?php
								}
							endforeach;
						?>
					</div>
					<?php endforeach; ?>
				<?=form_close();?>
			</div>
			<?php endif; ?>
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

			<?php if($this->sideForm): ?>
			<!-- Modal to add new record -->
			<div
				class="offcanvas offcanvas-end"
				tabindex="-1"
				id="offcanvasRecord"
				data-bs-scroll="true"
				data-bs-backdrop="true"
				data-bs-keyboard="false"
				aria-labelledby="offcanvasLabel">
				<div class="offcanvas-header border-bottom">
					<h5 class="offcanvas-title" id="offcanvasLabel"><?=lang('New Record')?></h5>
					<button
							inert
							type="button"
							class="btn-close text-reset"
							data-bs-dismiss="offcanvas"
							aria-label="Close"></button>
				</div>
				<div class="offcanvas-body flex-grow-1">
					<?php builder_view("$platformName/layout/form_side", ['formType' => 'side', 'formData' => $formData]); ?>
				</div>
			</div>
			<!--/ Modal to add new record -->
			<?php endif; ?>

		</div>
	</div>
</div>
