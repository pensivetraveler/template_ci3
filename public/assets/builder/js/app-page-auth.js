let fv;

$(function() {
	const formSelector = '#formAuth';
	const formRecord = document.querySelector(formSelector);
	if(formRecord === null) throw new Error(`formRecord is not exist`);

	preparePlugins(formRecord);
	resetFrmInputs(formRecord, common.FORM_DATA);
	readyFrmInputs(formRecord, 'add', common.FORM_DATA);
	refreshPlugins();

	for(const rule of Object.keys(customValidatorsPreset.validators))
		FormValidation.validators[rule] = customValidatorsPreset.validators[rule];

	// Form validation for Add new record
	fv = FormValidation.formValidation(
		formRecord,
		{
			fields: reformatFormData(formRecord, common.FORM_DATA, common.FORM_REGEXP, true),
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
		submitAjax(formSelector, {
			success: function(response) {
				if(response.data.aul_key) setCookie('autologin', response.data.autologin, 30);
				if(response.data.result !== undefined) {
					let key = Object.keys(response.data.result)[0];
					let val = response.data.result[key];
					showAlert({
						type: 'success',
						html: `데이터가 조회되었습니다.<br><p class="text-danger mt-2 mb-0">(${getLocale('user.'+key, common.LOCALE)} : ${val})</p>`,
						callback: redirect,
						params: response.data.redirect_to??common.REDIRECT_URI??null,
					})
				}else{
					redirect(response.data.redirect_to??common.REDIRECT_URI??null)
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.warn(jqXHR.responseJSON)
				if(jqXHR.status === 422) {
					jqXHR.responseJSON.errors.forEach(error => {
						if(fv.fields.hasOwnProperty(error.param)) {
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
		});
	}).on('core.form.invalid', function () {
		// if fields are invalid
		console.log('core.form.invalid')
	});
});
