							<!-- Profiler modal -->
							<div class="modal fade" id="profilerModal" tabindex="-1" aria-hidden="true" data-backdrop="" data-keyboard="">
								<div class="modal-dialog modal-xl modal-simple modal-dialog-centered">
									<div class="modal-content">
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
										<div class="modal-body p-0">
											<div class="text-center mb-8">
												<h4>Benchmark</h4>
											</div>
											<div class="card">
												<div class="card-body ps-0 pe-0">
													<div class="nav-align-left">
														<ul class="nav nav-tabs" role="tablist">
															<li class="nav-item">
																<button
																		type="button"
																		class="nav-link active"
																		role="tab"
																		data-bs-toggle="tab"
																		data-bs-target="#navs-left-home"
																		aria-controls="navs-left-home"
																		aria-selected="true">
																	Home
																</button>
															</li>
														</ul>
														<div class="tab-content ps-8 pe-8">
															<div class="tab-pane fade show active" id="navs-left-home" role="tabpanel">
																<p>
																	Donut dragée jelly pie halvah. Danish gingerbread bonbon cookie wafer candy oat cake ice
																	cream. Gummies halvah tootsie roll muffin biscuit icing dessert gingerbread. Pastry ice
																	cream cheesecake fruitcake.
																</p>
																<p class="mb-0">
																	Jelly-o jelly beans icing pastry cake cake lemon drops. Muffin muffin pie tiramisu halvah
																	cotton candy liquorice caramels.
																</p>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="btn-home-wrap d-flex justify-content-center align-items-center mt-6 d-none">
												<button type="button" class="btn btn-primary waves-effect waves-light" onclick="location.href='<?=base_url('admin')?>'">Back to home</button>
											</div>
										</div>
									</div>
								</div>
							</div>
							<!-- / Profiler modal -->

							<!-- Error modal -->
							<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true" data-backdrop="" data-keyboard="" data-init="">
								<div class="modal-dialog modal-xl modal-simple modal-dialog-centered">
									<div class="modal-content">
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
										<div class="modal-body p-0">
											<div class="text-center mb-8">
												<h4>Oops somthing went wrong.</h4>
												<p id="errorModalMore" class="mt-2 hidden"></p>
											</div>
											<div class="card">
												<div class="card-body ps-0 pe-0">
													<div class="nav-align-left">
														<ul class="nav nav-tabs" role="tablist">
															<li class="nav-item">
																<button
																		type="button"
																		class="nav-link active"
																		role="tab"
																		data-bs-toggle="tab"
																		data-bs-target="#navs-left-home"
																		aria-controls="navs-left-home"
																		aria-selected="true">
																	Home
																</button>
															</li>
														</ul>
														<div class="tab-content ps-8 pe-8">
															<div class="tab-pane fade show active" id="navs-left-home" role="tabpanel">
																<p>
																	Donut dragée jelly pie halvah. Danish gingerbread bonbon cookie wafer candy oat cake ice
																	cream. Gummies halvah tootsie roll muffin biscuit icing dessert gingerbread. Pastry ice
																	cream cheesecake fruitcake.
																</p>
																<p class="mb-0">
																	Jelly-o jelly beans icing pastry cake cake lemon drops. Muffin muffin pie tiramisu halvah
																	cotton candy liquorice caramels.
																</p>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="no-error d-none">
												<div class="d-flex flex-column align-items-center text-primary">
													<i class="ri-chat-smile-3-line ri-48px"></i>
												</div>
											</div>
											<div class="btn-home-wrap d-flex justify-content-center align-items-center mt-6 d-none">
												<button type="button" class="btn btn-primary waves-effect waves-light" onclick="location.href='<?=base_url('admin')?>'">Back to home</button>
											</div>
										</div>
									</div>
								</div>
							</div>
							<!-- / Error modal -->
