let fv, offCanvasEl, fvBigCfg, offCanvasBigCfg;

/**
 * 드롭다운 선택지(한 줄에 하나, 값|표시명)를 [값, 표시명] 목록으로. 규칙은 Model_Sys_Cfg::parseOptions 와 같다
 */
function parseCfgOptions(text) {
    return String(text ?? '').split(/\r\n|\r|\n/).map(line => line.trim()).filter(line => line.length).map(line => {
        const index = line.indexOf('|');
        if(index < 0) return [line, line];
        const value = line.slice(0, index).trim();
        return [value, line.slice(index + 1).trim() || value];
    });
}

/**
 * 유형이 드롭다운이면 값 입력칸을 숨기고 선택 상자를 보여 준다. 고른 값은 입력칸에 넣어 저장 흐름은 그대로 쓴다
 */
function syncCfgValControl(form) {
    const input = form['cfg_val'];
    form.querySelector('#cfgValSelect')?.remove();
    input.style.display = '';
    if($(form['cfg_type']).val() !== 'dropdown') return;

    const select = document.createElement('select');
    select.id = 'cfgValSelect';
    select.className = 'form-select';
    const options = parseCfgOptions(form['cfg_options'].value);
    const disabled = (common.CFG_DISABLED_OPTIONS ?? {})[form['cmb_cfg']?.value] ?? [];
    if(!options.some(([value]) => value === '')) select.add(new Option(getLocale('Select', common.LOCALE), ''));
    options.forEach(([value, label]) => {
        const option = new Option(label, value);
        option.disabled = disabled.includes(value);
        select.add(option);
    });
    select.value = options.some(([value]) => value === input.value) ? input.value : '';
    select.addEventListener('change', function () {
        input.value = select.value;
        input.dispatchEvent(new Event('input', {bubbles: true}));
        input.dispatchEvent(new Event('change', {bubbles: true}));
    });
    // d-none 을 쓰면 테마 CSS 가 뒤따르는 라벨까지 숨긴다
    input.style.display = 'none';
    input.after(select);
}

$(function () {
    const formSelector = '#formRecord';
    const offCanvasElement = document.querySelector('#offcanvasRecord');
    if(offCanvasElement === null) throw new Error(`offCanvasElement is not exist`);

    const formRecord = document.querySelector(formSelector);
    if(formRecord === null) throw new Error(`formRecord is not exist`);

    onLoadedLayout(formRecord);
    preparePlugins(formRecord);

    offCanvasEl = new bootstrap.Offcanvas(offCanvasElement);

    offCanvasElement.addEventListener('show.bs.offcanvas', function(e) {
        console.log('offcanvas show');
        refreshPlugins(formRecord);
        document.getElementById('offcanvasLabel').textContent = getLocale(`${capitalize(this.querySelector(formSelector)['_mode'].value)} Record`, common.LOCALE);
    });

    offCanvasElement.addEventListener('shown.bs.offcanvas', function(e) {
        console.log('offcanvas shown');
    });

    offCanvasElement.addEventListener('hide.bs.offcanvas', function(e) {
        console.log('offcanvas hide');
    });

    offCanvasElement.addEventListener('hidden.bs.offcanvas', function(e) {
        resetFrmInputs(document.querySelector(formSelector), common.FORM_DATA);
        fv.resetForm(true);
        syncCfgValControl(formRecord);

        if ($('[data-repeater-item]').length) {
            $('[data-repeater-item]').each(function (i, v) {
                if(i > 0) $(v).remove();
            });
        }
    });

    $('.card-body').on('click', '.add-record', function () {
        if(!common.IDENTIFIER.length) throw new Error(`Identifier is not defined`);
        readyFrmInputs(formRecord, 'add', common.FORM_DATA);
        // 보고 있는 탭의 카테고리를 미리 고른다
        $(formRecord['big_cfg']).selectpicker('val', $(this).closest('.tab-pane').data('big-cfg'));
    });

    $('.card-body').on('click', '.edit-record', function() {
        if(!common.IDENTIFIER.length) throw new Error(`Identifier is not defined`);
        readyFrmInputs(formRecord, 'edit', common.FORM_DATA);
        fetchFrmValues(document.querySelector(formSelector), getRedirectActionData(this, common.IDENTIFIER));
    });

    $('.card-body').on('click', '.delete-record', function () {
        if(!common.IDENTIFIER.length) throw new Error(`Identifier is not defined`);
        deleteData(getRedirectActionData(this, common.IDENTIFIER), {
            callback: reload,
        });
    });

    formRecord.addEventListener('readyFrmInputs', (e) => {
        if(formRecord._mode.value !== 'edit') offCanvasEl.show();
    });

    $(formRecord['cfg_type']).on('change', () => syncCfgValControl(formRecord));
    $(formRecord['cfg_options']).on('input', () => syncCfgValControl(formRecord));

    formRecord.addEventListener("fetchFrmValues", (e) => {
        readyFrmInputs(formRecord, 'edit', common.FORM_DATA);
        applyFrmValues(formRecord, record, common.FORM_DATA);
        refreshPlugins(formRecord);
        syncCfgValControl(formRecord);
        offCanvasEl.show();
    });

    formRecord.addEventListener('transFrmValues', (e) => {
        offCanvasEl.hide();
    });

    for(const rule of Object.keys(customValidatorsPreset.validators))
        FormValidation.validators[rule] = customValidatorsPreset.validators[rule];

    const dropzoneList = common.FORM_DATA.filter((item) => item.subtype.indexOf('dropzone') !== -1);

    // Form validation for Add new record
    fv = FormValidation.formValidation(
        formRecord,
        {
            fields: reformatFormData(formRecord, common.FORM_DATA, common.FORM_REGEXP, false),
            plugins: {
                message: new FormValidation.plugins.Message({
                    container: function (field, element) {
                        // Dropzone 필드 메시지를 특정 컨테이너에 표시
                        if (dropzoneList.find((item) => item.field === field)) {
                            return document.querySelector(`#${field}-dropzone-wrapper`);
                        }
                        return element.closest('.form-validation-unit');
                    },
                }),
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    // Use this for enabling/changing valid/invalid class
                    // eleInvalidClass: '',
                    eleValidClass: '',
                    rowSelector: function(field, ele) {
                        switch (field) {
                            default:
                                return '.form-validation-unit';
                        }
                    },
                }),
                submitButton: new FormValidation.plugins.SubmitButton(),
                // submit button의 type을 submit으로 원할 경우
                // defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
                autoFocus: new FormValidation.plugins.AutoFocus(),
            },
            init: instance => {
                instance.on('plugins.message.placed', function (e) {
                    // 중복된 fv-plugins-message-container 제거
                    const containers = e.element.closest('.form-validation-unit').querySelectorAll('.fv-plugins-message-container');
                    if (containers.length > 1) containers[1].remove();

                    //* Move the error message out of the `input-group` element
                    if (e.element.parentElement.classList.contains('input-group')) {
                        // `e.field`: The field name
                        // `e.messageElement`: The message element
                        // `e.element`: The field element
                        e.element.parentElement.insertAdjacentElement('afterend', e.messageElement);
                    }
                });
            }
        }
    ).on('plugins.message.displayed', function (event) {
        // e.messageElement presents the error message element
    }).on('core.field.init', function(event) {
        // When a field is initialized, bind the input event to it
        var field = event.field;
        var element = event.elements[0];  // The field element
        element.addEventListener('change', function() {
            // Revalidate field when flatpickr
            if(element.classList.contains('.form-input_date-flatpickr')) fv.revalidateField(field);
            // Revalidate field whenever input changes
            // e.fv.revalidateField(field);
        });
    }).on('core.form.validating', function(event) {
        // 유효성 검사 시작 전
        console.log('%c The form validation has started.', 'color: green')
        const form = event.formValidation.form;
        if(form['_event'] !== undefined) form['_event'].value = 'submit';
    }).on('core.validator.validating', function(event) {
        // 특정 요소에 대한 유효성 검사 시작 전
        console.log('============================================================');
        console.log('%c Validator for the field ' + event.field + ' is validating.', 'color: skyblue');
        if(event.element.hasAttribute('data-textarea-id')) {
            if(event.element.getAttribute('data-textarea-id')) {
                const textareaId = event.element.getAttribute('data-textarea-id');
                event.element.value = editors[`${textareaId}`].root.innerHTML;
            }
        }
        console.log(event)
        console.log('value : ', event.element.value);
    }).on('core.validator.validated', function(event) {
        // 특정 요소에 대한 유효성 검사 시작 후
        console.log('%c Validator for the field ' + event.field + ' is validated.', 'color: skyblue');
        if(!event.result.valid) {
            console.log('------------------------------------------------------------');
            console.log('%c Validator for the field ' + event.field + ' is invalid.', 'color: red');
            console.log('Invalid validator:', event.validator);
            console.log('Invalid field:', event.field);
            console.log('Error message:', event.result.message);
            console.log('Result Object:',event.result)
            console.log('------------------------------------------------------------');
        }
    }).on('core.form.valid', function(event) {
        // 유효성 검사 완료 후
        updateFormLifeCycle('checkFrmValues', formRecord);

        // Send the form data to back-end
        // You need to grab the form data and create an Ajax request to send them
        submitAjax(formSelector, {
            success: function(response) {
                showAlert({
                    type: 'success',
                    title: 'Complete',
                    text: formRecord['_mode'].value === 'edit' ? 'Your Data Is Updated' : 'Registered Successfully',
                    callback: reload,
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.warn(jqXHR.responseJSON)
                if(jqXHR.status === 422) {
                    (jqXHR.responseJSON?.errors ?? []).forEach(error => {
                        if(Object.hasOwn(fv.fields, error.param)) {
                            fv.updateFieldStatus(error.param, 'Invalid', customValidatorsPreset.inflector(error.type));
                        }
                    });
                }else{
                    // 환경값 검사(SysCfg::checkCfgValue)는 errors 에 이유를 담아 보낸다
                    const errors = jqXHR.responseJSON?.errors;
                    showAlert({
                        type: 'warning',
                        text: Array.isArray(errors) && errors.length ? errors.map(error => error.msg).join('\n') : getAjaxErrorMessage(jqXHR),
                    });
                }
            }
        }, true);
    }).on('core.form.invalid', function () {
        // if fields are invalid
        console.log('core.form.invalid')
    });

    // 다시 불러와도 보던 탭을 유지한다
    const tabStorageKey = 'system-config-tab';
    $('.system-code-nav [data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        try { sessionStorage.setItem(tabStorageKey, this.dataset.bsTarget); } catch (e) {}
    });
    try {
        const savedTab = document.querySelector(`.system-code-nav [data-bs-target="${sessionStorage.getItem(tabStorageKey)}"]`);
        if(savedTab) bootstrap.Tab.getOrCreateInstance(savedTab).show();
    } catch (e) {}

    // 카테고리(소구분 000)
    const formBigCfgSelector = '#formBigCfg';
    const offCanvasBigCfgEl = document.querySelector('#offcanvasBigCfg');
    const formBigCfg = document.querySelector(formBigCfgSelector);
    if(formBigCfg === null) throw new Error(`formBigCfg is not exist`);

    onLoadedLayout(formBigCfg);
    preparePlugins(formBigCfg);

    offCanvasBigCfg = new bootstrap.Offcanvas(offCanvasBigCfgEl);

    offCanvasBigCfgEl.addEventListener('show.bs.offcanvas', function(e) {
        refreshPlugins(formBigCfg);
        document.getElementById('offcanvasBigCfgLabel').textContent = getLocale(`${capitalize(formBigCfg['_mode'].value)} Record`, common.LOCALE);
    });

    offCanvasBigCfgEl.addEventListener('hidden.bs.offcanvas', function(e) {
        resetFrmInputs(formBigCfg, common.EXTRA_FORMDATA);
        fvBigCfg.resetForm(true);
    });

    $('.card-body').on('click', '.add-big-cfg', function () {
        readyFrmInputs(formBigCfg, 'add', common.EXTRA_FORMDATA);
    });

    $('.card-body').on('click', '.edit-big-cfg', function () {
        readyFrmInputs(formBigCfg, 'edit', common.EXTRA_FORMDATA);
        fetchFrmValues(formBigCfg, {'cmb_cfg' : `${$(this).data('id')}_000`});
    });

    $('.card-body').on('click', '.delete-big-cfg', function () {
        deleteData({'cmb_cfg' : `${$(this).data('id')}_000`}, {
            callback: reload,
        });
    });

    formBigCfg.addEventListener('readyFrmInputs', (e) => {
        if(formBigCfg._mode.value !== 'edit') offCanvasBigCfg.show();
    });

    formBigCfg.addEventListener('fetchFrmValues', (e) => {
        readyFrmInputs(formBigCfg, 'edit', common.EXTRA_FORMDATA);
        applyFrmValues(formBigCfg, record, common.EXTRA_FORMDATA);
        refreshPlugins(formBigCfg);
        offCanvasBigCfg.show();
    });

    formBigCfg.addEventListener('transFrmValues', (e) => {
        offCanvasBigCfg.hide();
    });

    fvBigCfg = FormValidation.formValidation(
        formBigCfg,
        {
            fields: reformatFormData(formBigCfg, common.EXTRA_FORMDATA, common.FORM_REGEXP, true),
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    eleValidClass: '',
                    rowSelector: function(field, ele) {
                        return '.form-validation-unit';
                    },
                }),
                submitButton: new FormValidation.plugins.SubmitButton(),
                autoFocus: new FormValidation.plugins.AutoFocus(),
            },
        }
    ).on('core.form.validating', function(event) {
        const form = event.formValidation.form;
        if(form['_event'] !== undefined) form['_event'].value = 'submit';
    }).on('core.form.valid', function(event) {
        updateFormLifeCycle('checkFrmValues', formBigCfg);

        submitAjax(formBigCfgSelector, {
            success: function(response) {
                showAlert({
                    type: 'success',
                    title: 'Complete',
                    text: formBigCfg['_mode'].value === 'edit' ? 'Your Data Is Updated' : 'Registered Successfully',
                    callback: reload,
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.warn(jqXHR.responseJSON)
                // 키 형식(regex_match)·USER 차단은 화면 검사기가 없어 서버 문구를 그대로 보여 준다
                showAlert({
                    type: 'warning',
                    text: [400, 422].includes(jqXHR.status) ? (jqXHR.responseJSON?.errors ?? []).map(error => error.msg).join('\n') : getAjaxErrorMessage(jqXHR),
                });
            }
        }, true);
    });
});
