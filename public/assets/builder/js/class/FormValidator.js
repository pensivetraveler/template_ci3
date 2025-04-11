
class FormValidator {
	constructor(formSelector, fields, options = null) {
		this.formSelector = formSelector;
		this.rowSelector = function(field, ele) {
			switch (field) {
				default:
					return '.form-validation-unit';
			}
		};
		this.fields = fields;
		this.validator = null;

		if(option.rowSelector) {
			this.rowSelector = option.rowSelector;
		}

		// Custom validators 추가
		if (option.customValidatorsPreset && option.customValidatorsPreset.validators) {
			this.addCustomValidators(customValidatorsPreset.validators);
		}

		this.init();
	}

	// Custom validators 추가 메소드
	addCustomValidators(customValidators) {
		for (const rule of Object.keys(customValidators)) {
			FormValidation.validators[rule] = customValidators[rule];
		}
	}

	init() {
		// FormValidation 초기화
		this.validator = FormValidation.formValidation(
			document.querySelector(this.formSelector),  // 폼 셀렉터로 폼 선택
			{
				fields: this.fields,  // 전달받은 필드로 설정
				plugins: {
					trigger: new FormValidation.plugins.Trigger(),
					bootstrap5: new FormValidation.plugins.Bootstrap5({
						// Use this for enabling/changing valid/invalid class
						// eleInvalidClass: '',
						eleValidClass: '',
						rowSelector: this.rowSelector,
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
			const form = document.querySelector(this.formSelector);
			if(form['_event'] !== undefined) form['_event'].value = 'submit';
		}).on('core.validator.validating', function(event) {
			// 특정 요소에 대한 유효성 검사 시작 전
			console.log('%c Validator for the field ' + event.field + ' is validating.', 'color: skyblue');
			this.onValidatingEvent(event);
		}).on('core.validator.validated', function(event) {
			// 특정 요소에 대한 유효성 검사 시작 후
			console.log('%c Validator for the field ' + event.field + ' is validated.', 'color: skyblue');
			this.onValidatedEvent(event);
		}).on('core.form.invalid', function () {
			// if fields are invalid
			console.log('core.form.invalid')
		});
	}

	// 추가적인 메소드 정의 가능, 예를 들어 검증 실행
	validateField(fieldName) {
		return this.validator.validateField(fieldName);
	}

	validateForm() {
		return this.validator.validate();
	}

	onValidatingEvent(event) {
		if(event.element.hasAttribute('data-textarea-id')) {
			if(textareaId = event.element.getAttribute('data-textarea-id'))
				event.element.value = editors[`${textareaId}`].root.innerHTML;
		}
	}

	onValidatedEvent(event) {
		if(!event.result.valid) {
			console.log('------------------------------------------------------------');
			console.log('%c Validator for the field ' + event.field + ' is invalid.', 'color: red');
			console.log('Invalid validator:', event.validator);
			console.log('Invalid field:', event.field);
			console.log('Error message:', event.result.message);
			console.log('Result Object:',event.result)
			console.log('------------------------------------------------------------');
		}
	}
}
