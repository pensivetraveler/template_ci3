<div class="row g-6 mb-6">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb"><?=get_breadcrumbs($titleList)?></ol>
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
					<li class="nav-item">
						<button
							type="button"
							class="nav-link py-6"
							role="tab"
							data-bs-toggle="tab"
							data-bs-target="#navs-justified-config"
							aria-controls="navs-justified-config"
							aria-selected="false">
							<span class="d-none d-sm-block">
								환경값 설정
							</span>
						</button>
					</li>
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
					</div>
				</div>
				<div class="tab-pane" id="navs-justified-config" role="tabpanel">
					<?php foreach ($data as $bigCfg=>$list): ?>
					<div class="p-4 mb-4">
						<h5 class="text-primary fw-bolder"><?=$bigCfg?></h5>
						<?php foreach ($list as $item): ?>
						<div class="row mb-3">
							<label for="form_page-family_name" class="col-sm-2 col-form-label"><?=$item['cfg_name']?></label>
							<div class="col-sm-10">
								<div class="input-group input-group-merge">
									<input type="<?=$item['cfg_type']==='password'?'password':'text'?>" name="<?=$item['cmb_cfg']?>" value="<?=$item['cfg_val']?>" id="<?=$item['cmb_cfg']?>" class="form-control dt-family_name form-input_base form-input_text-base" readonly>
									<?php if($isSystemAdmin): ?>
									<span class="input-group-text text-primary cursor-pointer edit-record" data-id="<?=$item['cmb_cfg']?>"><i class="ri-edit-box-line"></i></span>
									<span class="input-group-text text-danger cursor-pointer delete-record" data-id="<?=$item['cmb_cfg']?>"><i class="ri-close-circle-fill"></i></span>
									<?php endif; ?>
								</div>
								<div class="form-text ms-1 <?=!$item['cfg_desc']?'d-none':''?>"><?=$item['cfg_desc']?></div>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
					<?php endforeach; ?>

					<?php if($isSystemAdmin): ?>
					<div class="row">
						<div class="col-sm-12 text-end">
							<button type="button" class="btn btn-outline-primary waves-effect add-record"><?=lang('Add')?></button>
						</div>
					</div>
					<?php endif; ?>
				</div>
			</div>
        </div>

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
