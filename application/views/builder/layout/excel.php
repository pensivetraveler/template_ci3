<div class="row g-6 mb-6">
	<nav aria-label="breadcrumb">
		<ol class="breadcrumb"><?=get_admin_breadcrumbs($titleList)?></ol>
	</nav>
</div>
<div class="row g-6 mb-6">
	<div class="card mb-6">
		<div class="card-header border-bottom">
			<?=form_open('', [
					'id' => 'formExcel',
					'name' => 'formExcel',
					'class' => 'form-type-excel'
			], [
					'_onloaded' => 0,
			]); ?>
			<input type="hidden" name="origData" value="">
			<h5 class="mb-0"><?=lang('nav.'.$titleList[1])?> 엑셀 업로드</h5>
			<span class="small d-block mt-1 text-gray">엑셀 업로드 관리 페이지입니다.</span>
			<div class="row mt-4">
				<div class="col-6">
					<div>
						<h6 class="mb-1">유의사항</h6>
						<p class="text-start mb-0">
							- 파일제한 : <?=UPLOAD_MAX_FILESIZE_TXT?>B
						</p>
						<p class="text-start mb-0">
							- 허용파일 : 파일 확장자 <span class="h6">.xlsx</span> 파일
						</p>
					</div>
					<div class="mt-4">
						<h6 class="mb-1">작성 시 주의사항</h6>
						<p class="text-start mb-0">
							- 반드시 아래 샘플 양식을 활용하고 불필요한 빈칸이 없도록 작성합니다.
						</p>
						<p class="text-start mb-0">
							- 빈 칸을 주의해서 작성해주세요.
						</p>
						<p class="text-start mb-0">
							- 샘플 양식을 활용 시 첫번째 행은 삭제하지 마십시오.
						</p>
						<p class="text-start mt-4">
							<a href="<?=$sampleFile?>" class="btn btn-secondary p-2" download=""><i class="ri-download-line ri-14px me-2"></i><span class="small">샘플 파일 다운로드</span></a>
						</p>
					</div>
				</div>
				<div class="col-6">
					<h6 class="mb-1">업로드 내역</h6>
					<ul class="mt-2 p-4 rounded-3 bg-label-primary">
						<li class="mb-2 d-flex align-items-center">
							<i class="ri-circle-fill text-body ri-10px me-2"></i>
							<span class="me-2">총 행 개수 : </span>
							<span id="totalRowCount">0</span>개
						</li>
						<li class="mb-2 d-flex align-items-center">
							<i class="ri-circle-fill text-body ri-10px me-2"></i>
							<span class="me-2">오류 데이터 수 : </span>
							<span id="totalErrorCount">0</span>개
						</li>
						<li class="mb-2 d-flex align-items-center">
							<button type="button" class="btn btn-primary p-2" id="btnErrorFind" disabled><small>오류 바로가기</small></button>
						</li>
					</ul>
				</div>
			</div>
			<div class="row mt-8">
				<div class="col-md-6 col-sm-12">
					<div class="input-group input-group-merge">
						<div class="form-floating form-floating-outline">
							<input type="file" name="excelFile" id="excelFile"
								   accept=".xls, .xlsx, application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
								   class="form-control">
							<label for="form_side-thumbnail">엑셀업로드</label>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-sm-12 d-flex align-items-center justify-content-end">
					<button id="resetExcelForm" class="btn btn-outline-primary w-px-100 btn-reset waves-effect me-4" type="reset">초기화</button>
					<button id="excelFormSubmit" class="btn btn-primary w-px-100 btn-search waves-effect waves-light" type="button" disabled>저장</button>
				</div>
			</div>
			<?=form_close();?>
		</div>
		<div class="card-body">
			<div>
				<ul>

				</ul>
			</div>
			<div class="table-responsive text-nowrap mt-4">
				<table class="table table-centered table-bordered mb-0 table-nowrap" id="inline-editable">
					<thead>
					<tr>
						<th>#</th>
						<?php foreach ($excelHeaders as $item): ?>
						<th data-field="<?=$item['field']?>" data-required="<?=$item['required']?>"><?=lang($item['label'])?></th>
						<?php endforeach; ?>
						<th>삭제</th>
					</tr>
					</thead>
					<tbody>
					<tr class="no-result">
						<td colspan="<?=count($excelHeaders)+2?>" class="text-center">파일을 업로드하세요.</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<script>
	const excelHeaders = JSON.parse('<?=json_encode($excelHeaders, JSON_UNESCAPED_UNICODE)?>');
</script>
