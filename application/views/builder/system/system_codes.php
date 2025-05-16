<div class="row g-6 mb-6">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb"><?=get_admin_breadcrumbs($titleList)?></ol>
    </nav>
</div>
<div class="row g-6 mb-6">
    <div class="card mb-6">
		<div class="card-header">
			<div class="nav-align-top system-code-nav">
				<ul class="nav nav-tabs nav-fill" role="tablist">
					<li class="nav-item" data-id="">
						<button
							type="button"
							class="nav-link active py-6"
							role="tab"
							data-bs-toggle="tab"
							data-bs-target="#navs-justified-caution"
							aria-controls="navs-justified-caution"
							aria-selected="true">
							<span class="d-none d-sm-block"><i class="icon-base ri ri-alert-fill text-danger icon-sm me-2"></i><?=lang('Caution')?></span>
						</button>
					</li>
					<?php foreach ($category as $i=>$item): ?>
					<li class="nav-item" data-id="<?=$item->big_cd?>">
						<button
							type="button"
							class="nav-link py-6"
							role="tab"
							data-bs-toggle="tab"
							data-bs-target="#navs-justified-list"
							aria-controls="navs-justified-list"
							aria-selected="false">
							<span class="d-none d-sm-block">
								<?=$item->cd_name?>
							</span>
						</button>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<div class="card-body">
			<div class="tab-content">
				<div class="tab-pane show active" id="navs-justified-caution" role="tabpanel">
					<div class="h-px-400">
						<h4>⚠️ 중요 안내</h4>
						<h6>이 화면에서 수정되는 모든 설정값은 시스템 전반에 즉시 반영되어, 메일 송·수신, 결제 연동, 외부 API 호출 등 주요 기능에 직접적인 영향을 미칩니다.</h6>
						<h6>비인가 사용자가 임의로 변경할 경우 서비스 장애나 보안 사고가 발생할 수 있으니, 반드시 권한이 부여된 담당자만 접근해 주세요.</h6>
						<h6>변경 전에는 항상 데이터베이스 백업을 수행하고, 테스트 환경에서 충분히 검증한 후 운영 서버에 적용하시기 바랍니다.</h6>
						<h6>설정값 입력 시 오타나 불완전한 정보가 입력되지 않도록, 값을 복사/붙여넣기 하신 후에도 재확인해 주십시오.</h6>
						<h6 class="mb-0">의문사항이 있거나 수정이 어려우신 경우, 시스템 관리자 또는 개발 담당자에게 문의해 주시기 바랍니다.</h6>
						<?php if($isSystemAdmin): ?>
						<button type="button" class="add-big-cd btn btn-primary data-submit me-sm-4 me-1 mt-8"><?=lang('Add Big Cd')?></button>
						<?php endif; ?>
					</div>
				</div>
				<div class="tab-pane" id="navs-justified-list" role="tabpanel">
					<div class="card-datatable table-responsive">
						<table class="datatables-records table">
							<thead>
							<tr>
								<th></th>
								<?php foreach ($columns as $column): ?>
								<th><?=lang($column['label'])?></th>
								<?php endforeach; ?>
							</tr>
							</thead>
						</table>
					</div>
				</div>
			</div>
		</div>

		<!-- Modal to add new record -->
		<div
				class="offcanvas offcanvas-end"
				tabindex="-1"
				id="offcanvasBigCd"
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
				<?php builder_view("builder/layout/form_side", ['formType' => 'side', 'formData' => $bigCdFormData, 'formName' => 'formBigCd']); ?>
			</div>
		</div>
		<!--/ Modal to add new record -->

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
				<h5 class="offcanvas-title" id="offcanvasLabel"><?=lang('Add Record')?></h5>
				<button
						inert
						type="button"
						class="btn-close text-reset"
						data-bs-dismiss="offcanvas"
						aria-label="Close"></button>
			</div>
			<div class="offcanvas-body flex-grow-1">
				<?php builder_view("builder/layout/form_side", ['formType' => 'side', 'formData' => $formData]); ?>
			</div>
		</div>
		<!--/ Modal to add new record -->

	</div>
</div>