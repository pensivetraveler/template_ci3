/**
 * GetLocale
 *
 * Fetches a language variable
 *
 * @param	string	key		The language line
 * @param	array	locale	Fetched language
 * @returns {*[]}
 */
function getLocale(key, locale = []) {
	try {
		if(locale.length === 0 && common.LOCALE === undefined) throw new ReferenceError('getLocale : LOCALE is not defined.');
		if(locale.length === 0) locale = common.LOCALE;
		if(key === undefined) throw new ReferenceError('getLocale : key is not defined.');

		let exist = false;
		let result = locale;

		if(key.indexOf('.') === -1 || key.substring(key.length-1,key.length) === '.'){
			result = locale.hasOwnProperty(key) ? result[key] : undefined;
		}else{
			const keys = key.split('.');

			for(const item of keys) {
				if(result[item] !== undefined) {
					result = result[item];
				}else{
					result = undefined;
					break;
				}
			}

			if(result === undefined) {
				result = locale;
				keys[0] = 'common';
				for(const item of keys) {
					if(result[item] !== undefined) {
						result = result[item];
					}else{
						result = undefined;
						break;
					}
				}
			}
		}

		if(result === undefined) throw new RangeError(`getLocale : Can't find locale. ${key}`);
		return result;
	} catch (error) {
		if (error instanceof RangeError) {
			console.warn(error.message);
			return key;
		}else{
			customErrorHandler(error);
		}
	}
}

/**
 * FoldDaumPostcode
 * @param wrap
 */
function foldDaumPostcode(wrap) {
	// iframe을 넣은 element를 안보이게 한다.
	wrap.classList.remove('on');
	wrap.style.removeProperty('height');
	wrap.querySelector('div').remove();
}

function findAddress(wrap) {
	const group_name = wrap.getAttribute('data-group-name');
	// 현재 scroll 위치를 저장해놓는다.
	new daum.Postcode({
		oncomplete: function(data) {
			const frm = wrap.closest('form');
			console.log(`[data-group-name="${group_name}"][data-group-field="zipcode"]`);
			frm.querySelector(`[data-group-name="${group_name}"][data-group-key="zipcode"]`).value = data.zonecode;
			frm.querySelector(`[data-group-name="${group_name}"][data-group-key="addr1"]`).value = data.address;
			frm.querySelector(`[data-group-name="${group_name}"][data-group-key="addr2"]`).focus()

			// iframe을 넣은 element를 안보이게 한다.
			// (autoClose:false 기능을 이용한다면, 아래 코드를 제거해야 화면에서 사라지지 않는다.)
			wrap.classList.remove('on');
		},
		// 우편번호 찾기 화면 크기가 조정되었을때 실행할 코드를 작성하는 부분. iframe을 넣은 element의 높이값을 조정한다.
		onresize : function(size) {
			wrap.style.height = size.height+'px';
		},
		width : '100%',
		height : '100%'
	}).embed(wrap);

	// iframe을 넣은 element를 보이게 한다.
	wrap.classList.add('on');
}

function callUserFunc(callback = undefined, params = undefined) {
	try {
		if(callback === undefined || typeof callback === undefined)
			throw new Error(`callUserFunc : callback is not defined !`);

		if(typeof callback === 'string' && callback.trim().length === 0)
			throw new Error(`callUserFunc : callback is not valid !`);

		const isString = typeof callback === 'string';

		if(params !== undefined) {
			if(isObject(params) || isArray(params)) {
				if(isObject(params)) isString?window[callback](params):callback(params);
				if(isArray(params)) isString?window[callback](...params):callback(...params);
			}else{
				isString?window[callback](params):callback(params);
			}
		}else{
			isString?window[callback]():callback();
		}
	} catch (error) {
		customErrorHandler(error);
	}
}

function showAlert(obj = {}) {
	try {
		var title, text;
		if(obj.type === undefined) obj.type = 'success';
		if(!['success', 'warning', 'error'].includes(obj.type)) throw new Error(`showAlert : Type is not allowed. ${obj.type}`);
		if(obj.title !== undefined) title = getLocale(obj.title, common.LOCALE);
		if(obj.text !== undefined || obj.html !== undefined) text = getLocale(obj.text, common.LOCALE);

		switch (obj.type) {
			case 'success' :
				if(title === undefined) title = 'Success!';
				if(text === undefined) text = getLocale('You clicked the button!', common.LOCALE);
				break;
			case 'warning' :
				if(title === undefined) title = 'Warning!';
				if(text === undefined) text = getLocale('Are you sure you want to do this?', common.LOCALE);
				break;
			case 'error' :
				if(title === undefined) title = 'Error!';
				if(text === undefined) text = getLocale('An Error Occurred', common.LOCALE);
				break;
		}

		obj.title = title;
		if(obj.html === undefined) obj.text = text;

		showSwalAlert(obj);
	} catch (error) {
		customErrorHandler(error);
	}
}

function swalKeydownHandler(event) {
	// Prevent bubbling of Enter or Escape key
	if (event.key === 'Enter' || event.key === 'Escape') {
		event.stopPropagation();
		event.preventDefault();
		// Confirm the swal
		if(['Enter', 'Escape'].includes(event.key)) Swal.clickConfirm();
	}
}

function showSwalAlert(obj) {
	Swal.fire({
		title: obj.title,
		html: nl2br(obj.text??obj.html??null),
		icon: obj.type,
		customClass: {
			confirmButton: 'btn btn-primary waves-effect waves-light'
		},
		buttonsStyling: false,
		willOpen: () => {
			// console.log('1 willOpen')
			document.addEventListener('keydown', swalKeydownHandler);
		},
		preConfirm: () => {
			// console.log('2 preConfirm')
			console.log('Confirm button clicked or Enter key pressed');
		},
		didOpen: () => {
			// console.log('3 didOpen')
		},
		willClose: () => {
			// console.log('4 willClose')
			document.removeEventListener('keydown', swalKeydownHandler);
		},
	}).then(function (result) {
		if(obj.callback !== undefined) {
			if(obj.hasOwnProperty('params') && obj.params !== null){
				callUserFunc(obj.callback, obj.params);
			}else{
				obj.callback();
			}
		}else{
			// if(obj.type === 'error') location.reload();
		}
	});
}

function reformatFormData(form, data, regexp = {}, side = false) {
	if(data === undefined) {
		data = [];
		for(const input of form.querySelectorAll('input')) {
			if(['hidden','button'].includes(input.type)) continue;
			data.push({
				field: input.name,
				rules: 'required',
				errors: [],
			});
		}
	}

	return data.reduce((acc, curr, i) => {
		if(curr.type === 'hidden') return acc;

		let selector;
		if(form.querySelector(`[name="${curr.field}"]`)) {
			selector = `[name="${curr.field}"]`;
		}else if(form.querySelector(`[name="${curr.field}[]"]`)) {
			selector = `[name="${curr.field}[]"]`;
		}else if(curr.group) {
			const groupName = curr.group;
			const inputName = curr.field;
			if(curr.group_attributes.envelope_name) {
				selector = `[name^="${groupName}"][name$="[${inputName}]"]`;
			}else{
				selector = `[name^="${inputName}"]`;
			}
			selector = isValidSelector(selector) ? selector : null;
		}else if(isValidSelector(`#${curr.id}`) && form.querySelector(`#${curr.id}`)) {
			selector = `#${curr.id}`;
		}
		if(!selector) return acc;

		const item = {
			selector : selector,
			validators : {},
		};

		for(const key of Object.keys(curr.errors)){
			switch(key) {
				case 'required':
					item.validators.notEmpty = {
						message: curr.errors[key]
					};
					break;
			}
		}

		if(curr.type === 'date') {
			item.validators.date = {
				format: 'YYYY-MM-DD',
			};
		}

		if(curr.type === 'file') {
			console.log(curr.attributes.accept)
			item.validators.file = {
				extension: curr.attributes.extension??null,
				maxFiles : curr.attributes.max??null,
				type : curr.attributes.accept??null,
				message : '유효한 파일을 업로드해주세요.',
			};
		}

		curr.rules.split(/\|(?![^\[]*\])/).forEach(raw => {
			if(!raw.length) return;
			if(['required', 'trim'].includes(raw)) return;
			const rule = raw.match(/^[a-zA-Z_]+/)?.[0];

			if (!rule || ![...Object.keys(customValidatorsPreset.rules), ...Object.keys(regexp)].includes(rule)) {
				console.warn(`reformatFormData : Rule '${rule || raw}' of '${curr.field}' doesn't have any matched validator.`);
				// item.validators['baseValidator'] = {
				// 	message: `The field id not valid (${camelize(rule)})`,
				// }
				return;
			}else{
				if (customValidatorsPreset.inflector(rule)) {
					const validatorName = customValidatorsPreset.inflector(rule)
					const { regex, options: getOptions, message: getMessage } = customValidatorsPreset.rules[rule];

					let message;
					if(getMessage === undefined) {
						message = curr.errors?.[rule] && curr.errors[rule];
					}else{
						message = getMessage;
					}

					if (!regex) {
						console.warn(`${rule} regex is not set.`);
						item.validators[validatorName] = {
							...(message && { message: message })
						};
					}else{
						if(customValidatorsPreset.extractor(regex, raw)){
							item.validators[validatorName] = {
								...item.validators[validatorName],
								...getOptions(form, item, customValidatorsPreset.extractor(regex, raw)),
								...(message && { message: message })
							};
						}
					}
				}else{
					if(Object.keys(regexp).includes(rule)) {
						item.validators['regexp'] = {
							regexp: new RegExp(regexp[rule].exp, regexp[rule].flags)
						};
						if(curr.errors.hasOwnProperty(rule)) item.validators['regexp'].message = curr.errors[rule];
					}else{
						console.warn(`reformatFormData : ${rule} validator is not set.`);
					}
				}
			}
		});

		acc[curr.field] = item;
		return acc; // 항상 acc를 반환
	}, {});
}

function setFlatpickr(node) {
	const option = {
		// See https://flatpickr.js.org/formatting/
		dateFormat: 'Y-m-d',
		positionElement: document.querySelector(`#${node.id}`),
		onReady: function(selectedDates, dateStr, instance) {
			// Center the popup relative to the input
			instance.calendarContainer.classList.add('flatpickr-side-position');
		},
		// After selecting a date, we need to revalid”ate the field
		onChange: function (data, value, full) {
			$(full.input).trigger('change'); // 'change' 이벤트 강제로 발생
		},
	};

	if(node.classList.contains('flatpickr-date')) {
		Object.assign(option, {
			enableTime: false,
			dateFormat: 'Y-m-d',
		})
	}else if(node.classList.contains('flatpickr-time')) {
		Object.assign(option, {
			enableTime: true,
			noCalendar: true,
			// See https://flatpickr.js.org/formatting/
			dateFormat: 'h:i K',
			// time_24hr: false,
		})
	}

	node.flatpickr(option);
}

function setCleave(node) {
	if(node.classList.contains('cleave-hp')) {
		new Cleave(node, {
			phone: true,
			delimiter: '-',
			phoneRegionCode: 'KR'
		});
	}else if(node.classList.contains('cleave-fulldate')) {
		new Cleave(node, {
			date: true,
			delimiter: '-',
			datePattern: ['Y', 'm', 'd']
		});
	}else if(node.classList.contains('cleave-year')) {
		new Cleave(node, {
			date: true,
			datePattern: ['Y']
		});
	}else if(node.classList.contains('cleave-month')) {
		new Cleave(node, {
			date: true,
			datePattern: ['m']
		});
	}else if(node.classList.contains('cleave-date')) {
		new Cleave(node, {
			date: true,
			datePattern: ['d']
		});
	}else if(node.classList.contains('cleave-time')) {
		new Cleave(node, {
			time: true,
			timePattern: ['h', 'm']
		});
	}else if(node.classList.contains('cleave-hour')) {
		new Cleave(node, {
			time: true,
			timePattern: ['h']
		});
	}else if(node.classList.contains('cleave-minute')) {
		new Cleave(node, {
			time: true,
			timePattern: ['m']
		});
	}else if(node.classList.contains('form-input_text-cleave-version')) {
		new Cleave(node, {
			delimiter: '.',
			blocks: [1, 1, 1],
			uppercase: false,
			numericOnly: true
		});
	}
}
