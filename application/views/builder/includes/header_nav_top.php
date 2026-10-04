<nav
		class="layout-navbar navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme container-<?=!isset($containerFluid)||$containerFluid?'fluid':'xxl'?>"
		id="layout-navbar">
	<div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
		<a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
			<i class="ri-menu-fill ri-22px"></i>
		</a>
	</div>

	<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
		<!-- Search -->
<!--		<div class="navbar-nav align-items-center">-->
<!--			<div class="nav-item navbar-search-wrapper mb-0">-->
<!--				<a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">-->
<!--					<span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>-->
<!--				</a>-->
<!--			</div>-->
<!--		</div>-->
<!--		<div class="navbar-nav align-items-center">-->
<!--			<div class="nav-item navbar-search-wrapper mb-0">-->
<!--				<a class="nav-item nav-link search-toggler fw-normal px-0" href="javascript:void(0);">-->
<!--					<i class="ri-search-line ri-22px scaleX-n1-rtl me-3"></i>-->
<!--					<span class="d-none d-md-inline-block text-muted">Search (Ctrl+/)</span>-->
<!--				</a>-->
<!--			</div>-->
<!--		</div>-->
		<!-- /Search -->

		<ul class="navbar-nav flex-row align-items-center ms-auto">
			<!-- Profiler -->
			<li class="nav-item nav-profiler">
				<a
						class="nav-link btn btn-text-secondary rounded-pill btn-icon"
						href="javascript:void(0);"
						data-bs-toggle="modal"
						data-bs-target="#profilerModal">
					<i class="ri-dashboard-2-line ri-22px"></i>
					<span class="d-none position-absolute top-0 start-50 translate-middle-y badge badge-dot bg-danger mt-2 border"></span>
				</a>
			</li>
			<!--/ Profiler -->

			<!-- Error -->
			<li class="nav-item nav-error">
				<a
						class="nav-link btn btn-text-secondary rounded-pill btn-icon"
						href="javascript:void(0);"
						data-bs-toggle="modal"
						data-bs-target="#errorModal">
					<i class="ri-error-warning-line ri-22px"></i>
					<span class="d-none position-absolute top-0 start-50 translate-middle-y badge badge-dot bg-danger mt-2 border"></span>
				</a>
			</li>
			<!--/ Error -->

			<!-- Notification -->
			<!--li class="nav-item dropdown-notifications navbar-dropdown dropdown me-4 me-xl-1">
				<a
						class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow"
						href="javascript:void(0);"
						data-bs-toggle="dropdown"
						data-bs-auto-close="outside"
						aria-expanded="false">
					<i class="ri-notification-2-line ri-22px"></i>
					<span
							class="position-absolute top-0 start-50 translate-middle-y badge badge-dot bg-danger mt-2 border"></span>
				</a>
				<ul class="dropdown-menu dropdown-menu-end py-0">
					<li class="dropdown-menu-header border-bottom py-50">
						<div class="dropdown-header d-flex align-items-center py-2">
							<h6 class="mb-0 me-auto">Notification</h6>
							<div class="d-flex align-items-center">
								<span class="badge rounded-pill bg-label-primary fs-xsmall me-2">8 New</span>
								<a
										href="javascript:void(0)"
										class="btn btn-text-secondary rounded-pill btn-icon dropdown-notifications-all"
										data-bs-toggle="tooltip"
										data-bs-placement="top"
										title="Mark all as read"
								><i class="ri-mail-open-line text-heading ri-20px"></i
									></a>
							</div>
						</div>
					</li>
					<li class="dropdown-notifications-list scrollable-container">
						<ul class="list-group list-group-flush">
							<li class="list-group-item list-group-item-action dropdown-notifications-item">
								<div class="d-flex">
									<div class="flex-shrink-0 me-3">
										<div class="avatar">
											<img src="../../assets/img/avatars/1.png" alt class="rounded-circle" />
										</div>
									</div>
									<div class="flex-grow-1">
										<h6 class="small mb-1">Congratulation Lettie 🎉</h6>
										<small class="mb-1 d-block text-body">Won the monthly best seller gold badge</small>
										<small class="text-muted">1h ago</small>
									</div>
									<div class="flex-shrink-0 dropdown-notifications-actions">
										<a href="javascript:void(0)" class="dropdown-notifications-read"
										><span class="badge badge-dot"></span
											></a>
										<a href="javascript:void(0)" class="dropdown-notifications-archive"
										><span class="ri-close-line ri-20px"></span
											></a>
									</div>
								</div>
							</li>
							<li class="list-group-item list-group-item-action dropdown-notifications-item">
								<div class="d-flex">
									<div class="flex-shrink-0 me-3">
										<div class="avatar">
											<span class="avatar-initial rounded-circle bg-label-danger">CF</span>
										</div>
									</div>
									<div class="flex-grow-1">
										<h6 class="mb-1 small">Charles Franklin</h6>
										<small class="mb-1 d-block text-body">Accepted your connection</small>
										<small class="text-muted">12hr ago</small>
									</div>
									<div class="flex-shrink-0 dropdown-notifications-actions">
										<a href="javascript:void(0)" class="dropdown-notifications-read"
										><span class="badge badge-dot"></span
											></a>
										<a href="javascript:void(0)" class="dropdown-notifications-archive"
										><span class="ri-close-line ri-20px"></span
											></a>
									</div>
								</div>
							</li>
							<li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
								<div class="d-flex">
									<div class="flex-shrink-0 me-3">
										<div class="avatar">
											<img src="../../assets/img/avatars/2.png" alt class="rounded-circle" />
										</div>
									</div>
									<div class="flex-grow-1">
										<h6 class="mb-1 small">New Message ✉️</h6>
										<small class="mb-1 d-block text-body">You have new message from Natalie</small>
										<small class="text-muted">1h ago</small>
									</div>
									<div class="flex-shrink-0 dropdown-notifications-actions">
										<a href="javascript:void(0)" class="dropdown-notifications-read"
										><span class="badge badge-dot"></span
											></a>
										<a href="javascript:void(0)" class="dropdown-notifications-archive"
										><span class="ri-close-line ri-20px"></span
											></a>
									</div>
								</div>
							</li>
							<li class="list-group-item list-group-item-action dropdown-notifications-item">
								<div class="d-flex">
									<div class="flex-shrink-0 me-3">
										<div class="avatar">
                                <span class="avatar-initial rounded-circle bg-label-success"
								><i class="ri-shopping-cart-2-line"></i
									></span>
										</div>
									</div>
									<div class="flex-grow-1">
										<h6 class="mb-1 small">Whoo! You have new order 🛒</h6>
										<small class="mb-1 d-block text-body">ACME Inc. made new order $1,154</small>
										<small class="text-muted">1 day ago</small>
									</div>
									<div class="flex-shrink-0 dropdown-notifications-actions">
										<a href="javascript:void(0)" class="dropdown-notifications-read"
										><span class="badge badge-dot"></span
											></a>
										<a href="javascript:void(0)" class="dropdown-notifications-archive"
										><span class="ri-close-line ri-20px"></span
											></a>
									</div>
								</div>
							</li>
							<li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
								<div class="d-flex">
									<div class="flex-shrink-0 me-3">
										<div class="avatar">
											<img src="../../assets/img/avatars/9.png" alt class="rounded-circle" />
										</div>
									</div>
									<div class="flex-grow-1">
										<h6 class="mb-1 small">Application has been approved 🚀</h6>
										<small class="mb-1 d-block text-body"
										>Your ABC project application has been approved.</small
										>
										<small class="text-muted">2 days ago</small>
									</div>
									<div class="flex-shrink-0 dropdown-notifications-actions">
										<a href="javascript:void(0)" class="dropdown-notifications-read"
										><span class="badge badge-dot"></span
											></a>
										<a href="javascript:void(0)" class="dropdown-notifications-archive"
										><span class="ri-close-line ri-20px"></span
											></a>
									</div>
								</div>
							</li>
							<li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
								<div class="d-flex">
									<div class="flex-shrink-0 me-3">
										<div class="avatar">
                                <span class="avatar-initial rounded-circle bg-label-success"
								><i class="ri-pie-chart-2-line"></i
									></span>
										</div>
									</div>
									<div class="flex-grow-1">
										<h6 class="mb-1 small">Monthly report is generated</h6>
										<small class="mb-1 d-block text-body">July monthly financial report is generated </small>
										<small class="text-muted">3 days ago</small>
									</div>
									<div class="flex-shrink-0 dropdown-notifications-actions">
										<a href="javascript:void(0)" class="dropdown-notifications-read"
										><span class="badge badge-dot"></span
											></a>
										<a href="javascript:void(0)" class="dropdown-notifications-archive"
										><span class="ri-close-line ri-20px"></span
											></a>
									</div>
								</div>
							</li>
							<li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
								<div class="d-flex">
									<div class="flex-shrink-0 me-3">
										<div class="avatar">
											<img src="../../assets/img/avatars/5.png" alt class="rounded-circle" />
										</div>
									</div>
									<div class="flex-grow-1">
										<h6 class="mb-1 small">Send connection request</h6>
										<small class="mb-1 d-block text-body">Peter sent you connection request</small>
										<small class="text-muted">4 days ago</small>
									</div>
									<div class="flex-shrink-0 dropdown-notifications-actions">
										<a href="javascript:void(0)" class="dropdown-notifications-read"
										><span class="badge badge-dot"></span
											></a>
										<a href="javascript:void(0)" class="dropdown-notifications-archive"
										><span class="ri-close-line ri-20px"></span
											></a>
									</div>
								</div>
							</li>
							<li class="list-group-item list-group-item-action dropdown-notifications-item">
								<div class="d-flex">
									<div class="flex-shrink-0 me-3">
										<div class="avatar">
											<img src="../../assets/img/avatars/6.png" alt class="rounded-circle" />
										</div>
									</div>
									<div class="flex-grow-1">
										<h6 class="mb-1 small">New message from Jane</h6>
										<small class="mb-1 d-block text-body">Your have new message from Jane</small>
										<small class="text-muted">5 days ago</small>
									</div>
									<div class="flex-shrink-0 dropdown-notifications-actions">
										<a href="javascript:void(0)" class="dropdown-notifications-read"
										><span class="badge badge-dot"></span
											></a>
										<a href="javascript:void(0)" class="dropdown-notifications-archive"
										><span class="ri-close-line ri-20px"></span
											></a>
									</div>
								</div>
							</li>
							<li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
								<div class="d-flex">
									<div class="flex-shrink-0 me-3">
										<div class="avatar">
                                <span class="avatar-initial rounded-circle bg-label-warning"
								><i class="ri-error-warning-line"></i
									></span>
										</div>
									</div>
									<div class="flex-grow-1">
										<h6 class="mb-1 small">CPU is running high</h6>
										<small class="mb-1 d-block text-body"
										>CPU Utilization Percent is currently at 88.63%,</small
										>
										<small class="text-muted">5 days ago</small>
									</div>
									<div class="flex-shrink-0 dropdown-notifications-actions">
										<a href="javascript:void(0)" class="dropdown-notifications-read"
										><span class="badge badge-dot"></span
											></a>
										<a href="javascript:void(0)" class="dropdown-notifications-archive"
										><span class="ri-close-line ri-20px"></span
											></a>
									</div>
								</div>
							</li>
						</ul>
					</li>
					<li class="border-top">
						<div class="d-grid p-4">
							<a class="btn btn-primary btn-sm d-flex" href="javascript:void(0);">
								<small class="align-middle">View all notifications</small>
							</a>
						</div>
					</li>
				</ul>
			</li-->
			<!--/ Notification -->

			<?php if(isset($headerData)): ?>
			<!-- User -->
			<li class="nav-item navbar-dropdown dropdown-user dropdown">
				<a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
					<div class="avatar avatar-online bg-primary rounded-circle d-flex justify-content-center align-items-center">
						<span class="text-white"><?=mb_substr($headerData['name'],0,1)?></span>
					</div>
				</a>
				<ul class="dropdown-menu dropdown-menu-end">
					<li>
						<a class="dropdown-item" href="<?=base_url('/admin/myInfo')?>">
							<div class="d-flex justify-content-between">
								<span class="fw-medium d-block small"><?=$headerData['name']?></span>
								<small class="text-muted"><?=$headerData['id']?></small>
							</div>
						</a>
					</li>
					<li>
						<div class="dropdown-divider"></div>
					</li>
					<li>
						<a class="dropdown-item" href="<?=base_url("/{$this->flag}/myInfo")?>">
							<i class="ri-user-3-line ri-22px me-3"></i><span class="align-middle"><?=lang('My Info')?></span>
						</a>
					</li>
					<li>
						<a class="dropdown-item" href="<?=base_url("/{$this->flag}/system")?>">
							<i class="ri-settings-4-line ri-22px me-3"></i><span class="align-middle"><?=lang('Settings')?></span>
						</a>
					</li>
					<li>
						<div class="dropdown-divider"></div>
					</li>
					<li>
						<div class="d-grid px-4 pt-2 pb-1">
							<a class="btn btn-sm btn-danger d-flex" href="javascript:logout();" target="_self">
								<small class="align-middle"><?=lang('Logout')?></small>
								<i class="ri-logout-box-r-line ms-2 ri-16px"></i>
							</a>
						</div>
					</li>
				</ul>
			</li>
			<!--/ User -->
			<?php endif; ?>
		</ul>
	</div>

	<!-- Search Small Screens -->
	<div class="navbar-search-wrapper search-input-wrapper container-xxl d-none">
		<input
				id="nav-search-input"
				type="text"
				class="form-control search-input container-xxl border-0"
				placeholder="Search..."
				aria-label="Search..." />
		<i class="ri-close-fill search-toggler cursor-pointer"></i>
	</div>
</nav>
