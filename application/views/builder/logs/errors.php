<link rel="stylesheet" href="/public/assets/builder/vendor/libs/spinkit/spinkit.css" />
<div class="row g-6 mb-6">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-custom-icon"><?=get_breadcrumbs($titleList)?></ol>
    </nav>
</div>
<div class="row g-6 position-relative" id="view-container">
    <div class="col-12">
        <div class="card">

			<?php builder_view("{$platformName}/layout/filter") ?>

			<div class="card-header border-bottom">
				<div class="row">
					<div class="col-sm-12 d-flex justify-content-between align-items-center">
						<div>
							<button type="button" class="btn rounded-pill btn-info btn-fab demo waves-effect waves-light w-px-100" id="btn-refresh">
								Refresh
							</button>

							<button type="button" class="btn rounded-pill btn-primary btn-fab demo waves-effect waves-light w-px-100" id="btn-play">
								<span class="icon-base ri ri-play-fill icon-22px me-2"></span>
								Play
							</button>
							<button type="button" class="btn rounded-pill btn-secondary btn-fab demo waves-effect waves-light w-px-100 d-none" id="btn-stop">
								<span class="icon-base ri ri-stop-fill icon-22px me-2"></span>
								Stop
							</button>
						</div>

						<div class="w-auto">
							<select name="" aria-controls="" class="form-select form-select-sm" id="log-page-length">
								<option value="10">10</option>
								<option value="25">25</option>
								<option value="50">50</option>
								<option value="100">100</option>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="card-body h-100 h-px-400">
				<div class="row">
					<div class="col-sm-12">
						<div class="pt-6 h-px-200 d-flex justify-content-center align-items-center" id="no-result">
							<p class="mb-0 text-center" id="no-result-message"><?=lang('No logs found')?></p>
						</div>

						<ul class="list-group list-group-timeline pt-6 d-none" id="log-list"></ul>
					</div>
				</div>
            </div>

			<div class="card-footer border-top">
				<div class="row pt-4">
					<div class="col-sm-12 col-md-6">
						<p class="mb-0" id="log-list-info">검색결과 총 0 개 데이터 중 0 ~ 0 표시</p>
					</div>
					<div class="col-sm-12 col-md-6">
						<nav aria-label="Page navigation">
							<ul class="pagination mb-2 float-end">
								<li class="page-item first disabled">
									<a class="page-link waves-effect" href="javascript:void(0);"><i class="icon-base ri ri-skip-back-mini-line icon-22px"></i></a>
								</li>
								<li class="page-item prev disabled">
									<a class="page-link waves-effect" href="javascript:void(0);"><i class="icon-base ri ri-arrow-left-s-line icon-22px"></i></a>
								</li>
								<li class="page-item active">
									<a class="page-link waves-effect" href="javascript:void(0);">1</a>
								</li>
								<li class="page-item next disabled">
									<a class="page-link waves-effect" href="javascript:void(0);"><i class="icon-base ri ri-arrow-right-s-line icon-22px"></i></a>
								</li>
								<li class="page-item last disabled">
									<a class="page-link waves-effect" href="javascript:void(0);"><i class="icon-base ri ri-skip-forward-mini-line icon-22px"></i></a>
								</li>
							</ul>
						</nav>
					</div>
				</div>
			</div>

			<div class="h-100 position-absolute w-100 d-flex justify-content-center align-items-center z-5 rounded-4 overflow-hidden" id="log-spinner">
				<div class="sk-grid">
					<div class="sk-grid-cube"></div>
					<div class="sk-grid-cube"></div>
					<div class="sk-grid-cube"></div>
					<div class="sk-grid-cube"></div>
					<div class="sk-grid-cube"></div>
					<div class="sk-grid-cube"></div>
					<div class="sk-grid-cube"></div>
					<div class="sk-grid-cube"></div>
					<div class="sk-grid-cube"></div>
				</div>
				<div class="position-absolute w-100 h-100 bg-black opacity-25"></div>
			</div>

        </div>
    </div>
</div>

<template id="log-item">
	<li class="list-group-item list-group-timeline-{flag} mb-4">
		<div class="item-header d-flex justify-content-between mb-2">
			<h6 class="mb-0">
				<a class="me-2" href="javascript:;">
					<span class="badge bg-label-{flag} rounded-pill text-uppercase">{level}</span>
				</a>
				{message}
			</h6>
			<small class="text-body-secondary">{time}</small>
		</div>
		<div class="item-contents {display}">
			<div class="row border border-1 rounded-4 mx-0">
				<div class="col-sm-3 py-2 px-4 border-right">
					<p class="fs-tiny mb-1 text-capitalize">location</p>
					<p class="mb-0">{location}</p>
				</div>
				<div class="col-sm-3 py-2 px-4 border-right">
					<p class="fs-tiny mb-1 text-capitalize">params</p>
					<p class="mb-0">{params}</p>
				</div>
				<div class="col-sm-3 py-2 px-4 border-right">
					<p class="fs-tiny mb-1 text-capitalize">value</p>
					<p class="mb-0">{value}</p>
				</div>
				<div class="col-sm-3 py-2 px-4 border-right">
					<p class="fs-tiny mb-1 text-capitalize">type</p>
					<p class="mb-0">{type}</p>
				</div>
			</div>
		</div>
	</li>
</template>
