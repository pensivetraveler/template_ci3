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
								class="nav-link active p-sm-6"
								role="tab"
								data-bs-toggle="tab"
								data-bs-target="#navs-justified-menuList"
								aria-controls="navs-justified-menuList"
								aria-selected="true">
							<span class="d-none d-sm-block"><?=lang('MenuList')?></span>
						</button>
					</li>
					<li class="nav-item" data-id="">
						<button
								type="button"
								class="nav-link p-sm-6"
								role="tab"
								data-bs-toggle="tab"
								data-bs-target="#navs-justified-menuConf"
								aria-controls="navs-justified-menuConf"
								aria-selected="false">
							<span class="d-none d-sm-block"><i id="notConfEqual" class="d-none icon-base ri ri-alert-fill text-danger icon-sm me-2"></i><?=lang('MenuConf')?></span>
						</button>
					</li>
				</ul>
			</div>
		</div>

		<div class="card-body">
			<div class="tab-content">
				<div class="tab-pane show active" id="navs-justified-menuList" role="tabpanel">
					<div class="row">
						<div class="col-12 mb-6 text-end">
							<button class="btn btn-outline-danger" id="addMenuCodeBtn"><?=lang('Add Menu Code')?></button>
							<button class="btn btn-outline-primary" id="addMenuBtn"><?=lang('Add New Menu')?></button>
							<button class="btn btn-primary" id="saveMenuBtn"><?=lang('Submit')?></button>
						</div>
						<div class="col-12">
							<div class="card">
								<div class="card-body">
									<div id="menuContainer" class="menu-container">
										<!-- Add Menu Items Dynamically -->
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- 메뉴 아이템 템플릿 -->
					<template id="menu-item-template">
						<div class="menu-item">
							<div class="menu-header">
								<span class="menu-title">New Menu Item</span>
								<div class="menu-actions">
									<button class="btn btn-sm btn-outline-primary add-submenu"><?=lang('Add New SubMenu')?></button>
									<button class="btn btn-sm btn-outline-secondary edit-menu"><?=lang('Edit')?></button>
									<button class="btn btn-sm btn-outline-danger delete-menu"><?=lang('Delete')?></button>
								</div>
							</div>
							<div class="submenu-container"></div>
						</div>
					</template>
				</div>
				<div class="tab-pane" id="navs-justified-menuConf" role="tabpanel">
					<div class="row">
						<div class="col-12 mb-6 text-end">
							<button class="btn btn-outline-danger" id="checkConfigBtn"><?=lang('Show Config')?></button>
							<button class="btn btn-outline-primary" id="refreshMenuBtn"><?=lang('Refresh Menu')?></button>
							<button class="btn btn-primary" id="saveCacheBtn"><?=lang('Caching')?></button>
						</div>
					</div>
					<div class="row menu-check-wrapper">
						<div class="col-md-6 d-none">
							<div class="card">
								<div class="card-header border-bottom">
									<h5 class="mb-0 text-center text-primary fw-bold"><?=lang('Config')?></h5>
								</div>
								<div class="card-body">
									<div class="menu-container p-4" id="menuConfContainer">
										<?=get_menu_list_tree($menuConfList); ?>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="card">
								<div class="card-header border-bottom">
									<h5 class="mb-0 text-center text-primary fw-bold"><?=lang('Cached')?></h5>
								</div>
								<div class="card-body">
									<div class="menu-container p-4" id="menuCachedContainer">
										<?=get_menu_list_tree($menuCachedList); ?>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="card">
								<div class="card-header border-bottom">
									<h5 class="mb-0 text-center text-primary fw-bold"><?=lang('DB')?></h5>
								</div>
								<div class="card-body">
									<div class="menu-container p-4" id="menuDBContainer">
									</div>
								</div>
							</div>
						</div>
					</div>
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

<!-- 메뉴 관리 스크립트 -->