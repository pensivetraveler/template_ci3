<div class="col-md-<?=$item['colspan']??6?> mb-6">
    <?=get_builder_form_label($item, ['class' => 'd-block col-form-label fs-6 text-primary py-0 mb-2 fw-bolder'])?>
    <div class="border border-1 border-input rounded-3 custom-carousel-simple-wrap p-2 domain-register-container">
        <div class="row">
            <div class="col-sm-12">
                <div class="input-group input-group-merge">
                    <span id="id-ico" class="input-group-text text-primary border-end-0">
                        <i class="ri-global-line"></i>
                    </span>
                    <div class="form-floating form-floating-outline">
                        <input
                            type="text"
                            name="domain"
                            id="domain-input"
                            autocapitalize="none"
                            autocomplete="off"
                            placeholder="IP 주소를 입력하세요."
                            data-detect-changed="false"
                            class="form-control dt-id form-input_base form-input_with-button form-input_custom"
                            data-btn-type="domain_check"
                        >
                        <label for="domain-input">IP 주소</label>
                    </div>
                    <button
                        id="form_grid-domain-add"
                        type="button"
                        data-rel-field="domain"
                        class="btn btn-outline-primary waves-effect btn-domain-check"
                        onclick="this.closest('.domain-register-init').__domainRegister.checkDomain()"
                        disabled="disabled">확인</button>
                </div>
            </div>
        </div>
        <div class="row mt-3 px-3">
            <ul class="col-sm-12 list-group list-group-flush registered-domain-list px-0" id="domain_list">

            </ul>
            <p class="text-center my-3 text-primary list-no-item">접근 거부할 IP 주소를 입력해주세요.</p>
        </div>
    </div>
</div>
