let fvBigCd, offCanvasBigCd;

$(function () {
    $('.datatables-records').DataTable().responsive.recalc()   // DOM 크기 변경 등에 맞춰 리스판시브 재계산
        .columns.adjust()      // 컬럼 폭도 다시 맞추고
        .draw(false);          // 페이징 유지;

    $('[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if($(this).parent('.nav-item').data('id')){
            $('.datatables-records').DataTable().responsive.recalc()   // DOM 크기 변경 등에 맞춰 리스판시브 재계산
                .columns.adjust()      // 컬럼 폭도 다시 맞추고
                .draw(false);          // 페이징 유지;
        }
    });

    $('.system-code-nav .nav-item').on('click', function(e) {
        if($(this).data('id')){
            common.API_PARAMS.big_cd = $(this).data('id');
            $('.datatables-records').DataTable().ajax.reload(null, false);
        }else{
            delete common.API_PARAMS.big_cd;
        }
    })

    const formBigCdSelector = '#formBigCd';
    const offCanvasBigCdEl = document.querySelector('#offcanvasBigCd');

    const formBigCd = document.querySelector(formBigCdSelector);
    if(formBigCd === null) throw new Error(`formBigCd is not exist`);
    preparePlugins(formBigCd);

    offCanvasBigCd = new bootstrap.Offcanvas(offCanvasBigCdEl);

    offCanvasBigCdEl.addEventListener('show.bs.offcanvas', function(e) {
        console.log('offcanvas show');
        refreshPlugins(formBigCd);
        document.getElementById('offcanvasLabel').textContent = getLocale(`${capitalize(this.querySelector(formBigCdSelector)['_mode'].value)} Record`, common.LOCALE);
    });

    offCanvasBigCdEl.addEventListener('shown.bs.offcanvas', function(e) {
        console.log('offcanvas shown');
    });

    offCanvasBigCdEl.addEventListener('hide.bs.offcanvas', function(e) {
        console.log('offcanvas hide');
    });

    offCanvasBigCdEl.addEventListener('hidden.bs.offcanvas', function(e) {
        resetFrmInputs(document.querySelector(formBigCdSelector), common.EXTRA_FORMDATA);
        fvBigCd.resetForm(true);

        if ($('[data-repeater-item]').length) {
            $('[data-repeater-item]').each(function (i, v) {
                if(i > 0) $(v).remove();
            });
        }
    });

    $('.add-big-cd').on('click', function (e) {
        readyFrmInputs(formBigCd, 'add', common.EXTRA_FORMDATA);
    })

    $('.edit-big-cd').on('click', function (e) {
        readyFrmInputs(formBigCd, 'edit', common.EXTRA_FORMDATA);
        fetchFrmValues(document.querySelector(formBigCdSelector), {
            'cmb_cd' : common.API_PARAMS.big_cd+'000'
        });
    })

    $('.delete-big-cd').on('click', function (e) {
        deleteData({'cmb_cd' : common.API_PARAMS.big_cd+'000'}, {
            callback: reload,
        });
    })

    formBigCd.addEventListener('readyFrmInputs', (e) => {
        if(formBigCd._mode.value !== 'edit') offCanvasBigCd.show();
    });

    formBigCd.addEventListener("fetchFrmValues", (e) => {
        readyFrmInputs(formBigCd, 'edit', common.FORM_DATA);
        applyFrmValues(formBigCd, record, common.FORM_DATA);
        refreshPlugins(formBigCd);
        offCanvasBigCd.show();
    });

    formBigCd.addEventListener('transFrmValues', (e) => {
        offCanvasBigCd.hide();
    });

    // Form validation for Add new record
    fvBigCd = FormValidation.formValidation(
        formBigCd,
        {
            fields: reformatFormData(formBigCd, common.EXTRA_FORMDATA, common.FORM_REGEXP, true),
            plugins: {
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
        submitAjax(formBigCdSelector, {
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
                    jqXHR.responseJSON.errors.forEach(error => {
                        if(Object.hasOwn(fv.fields, error.param)) {
                            fv.updateFieldStatus(error.param, 'Invalid', customValidatorsPreset.inflector(error.type));
                        }
                    });
                }else{
                    showAlert({
                        type: 'warning',
                        text: jqXHR.responseJSON.msg,
                    });
                }
            }
        }, true);
    }).on('core.form.invalid', function () {
        // if fields are invalid
        console.log('core.form.invalid')
    });
});