<div class="card">
	<div class="card-header">
		<div class="d-flex align-items-center justify-content-between">
			<h5 class="mb-0 w-100"><span></span></h5>
			<div class="btn-dropdown-wrapper">
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
						<?php
							if(count($buttons)) :
								foreach ($buttons as $button=>$attr):
						?>
						<a class="dropdown-item text-center btn-view-<?=$button?>" href="javascript:void(0);"><?=lang($attr['text'])?></a>
						<?php
								endforeach;
								echo '<hr class="my-2">';
							endif;
						?>
						<?php foreach ($actions as $action): ?>
						<a class="dropdown-item text-center btn-view-<?=$action?>" href="javascript:void(0);"><?=lang(ucfirst($action))?></a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="card-body">
		<?php
			echo form_open_multipart('', [
				'id' => 'formRecord',
				'name' => 'formRecord',
				'class' => "add-new-record needs-validation view-type-{$viewType}",
				'onsubmit' => 'return false',
			], [
				'_mode' => $this->router->method,
				'_event' => '',
			]);

			foreach ($viewData['hiddens'] as $item) :
				echo form_input(
					[
						'type' => $item['type'],
						'name' => $item['field'],
						'id' => $item['id'],
					],
					'',
					$item['attributes'],
				);
			endforeach;

			foreach ($viewData['fields'] as $item) :
		?>
		<div class="row mb-4" data-field-name="<?=$item['field']?>">
			<?=form_label(lang($item['label']), $item['id'], ['class' => 'col-sm-2 col-form-label fs-6 text-primary fw-bolder py-4'])?>
			<div class="col-sm-10">
				<?php if($item['type'] === 'view'): ?>
				<div class="w-100 d-flex align-items-center border-bottom text-sm-left py-4">
					<span id="<?=$item['id']?>"></span>
				</div>
				<?php else: ?>
				<div class="input-group input-group-merge">
					<?php
						echo get_admin_form_ico($item);
						echo get_page_form_input_by_type($item, 'page');
					?>
				</div>
				<?php endif; ?>
				<?php if(!empty($item['help_block'])) echo get_help_block_html($item['help_block']); ?>
			</div>
		</div>
		<?php
			endforeach;
		?>
		<div class="row mt-6">
			<div class="col-sm-6 text-start">
				<?php foreach ($buttons as $button=>$attr): ?>
				<button type="button" class="btn btn-outline-dark waves-effect btn-view-<?=$button?>"><?=lang($attr['text'])?></button>
				<?php endforeach; ?>
			</div>
			<div class="col-sm-6 text-end">
				<?php if(in_array('list', $actions)): ?>
				<button type="button" class="btn btn-outline-dark waves-effect btn-view-list"><?=lang('List')?></button>
				<?php endif; ?>
				<?php if(in_array('edit', $actions)): ?>
				<button type="button" class="btn btn-primary waves-effect btn-view-edit"><?=lang('Edit')?></button>
				<?php endif; ?>
				<?php if(in_array('delete', $actions)): ?>
				<button type="button" class="btn btn-outline-danger waves-effect btn-view-delete"><?=lang('Delete')?></button>
				<?php endif; ?>
			</div>
		</div>
		<?=form_close();?>
	</div>
</div>