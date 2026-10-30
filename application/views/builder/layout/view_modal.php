<?php
/**
 * 목록 화면의 모달 상세 보기 (list method properties.viewConfig)
 * 값은 app-page-view.js applyViewData 가, 열기·버튼 표시는 app-page-list.js openViewModal 이 맡는다.
 * 수정·삭제 버튼은 목록의 .edit-record·.delete-record 처리(옆 폼·수정 페이지·삭제)를 그대로 쓴다.
 */
?>
<div class="modal fade view-modal" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="viewModalLabel"><?=lang('View Record')?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?=lang('Close')?>"></button>
			</div>
			<div class="modal-body">
				<?php
					echo form_open_multipart('', [
						'id' => 'viewRecord',
						'name' => 'viewRecord',
						'class' => "view-form view-type-{$viewType}",
						'onsubmit' => 'return false',
					], [
						'_mode' => 'view',
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
				<div class="row mb-3" data-field-name="<?=$item['field']?>">
					<?=form_label(lang($item['label']), $item['id'], ['class' => 'col-sm-3 col-form-label fs-6 text-primary fw-bolder py-3'])?>
					<div class="col-sm-9">
						<?php if($item['type'] === 'view'): ?>
						<div class="w-100 d-flex align-items-center border-bottom text-sm-left py-3">
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
					echo form_close();
				?>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary waves-effect edit-record d-none"><?=lang('Edit')?></button>
				<button type="button" class="btn btn-outline-danger waves-effect delete-record d-none"><?=lang('Delete')?></button>
				<button type="button" class="btn btn-outline-secondary waves-effect" data-bs-dismiss="modal"><?=lang('Close')?></button>
			</div>
		</div>
	</div>
</div>
