let record;
let editors = {};

common.FORM_LIFECYCLE = {
	preparePlugins : false,
	resetFrmInputs : false,
	readyFrmInputs : false,
	fetchFrmValues : false,
	applyFrmValues : false,
	refreshPlugins : false,
	checkFrmValues : false,
	transFrmValues : false,
};

common.FORM_REPEATER = [];

function updateFormLifeCycle(state, form = null, detail = {}) {
	common.FORM_LIFECYCLE[state] = true;

	// Event trigger
	const target = form ?? window;
	if(form) {
		detail = Object.assign({
			formSelector: form.getAttribute('id'),
		}, detail);
	}
	target.dispatchEvent(
		new CustomEvent(state, {
			bubbles : false,
			cancelable : true,
			composed : false,
			detail : detail,
		}),
	);
}

function preparePlugins(form) {
	// select picker
	if ($(form).find('.selectpicker').length) {
		$(form).find('.selectpicker').selectpicker();
		$(form).find('.form-floating:has(.selectpicker)').each(function () {
			$(this).addClass('form-floating-bootstrap-select');
		});
	}

	// select2
	if ($(form).find('.select2').length) {
		$(form).find('.select2').each(function () {
			var $this = $(this);
			$this.prepend('<option value="" disabled selected></option>');
			select2Focus($this);

			const option = {
				allowClear: true,
				placeholder: $this.attr('placeholder'),
				dropdownParent: $this.parent()
			}

			if(appPlugins.hasOwnProperty('select2') && appPlugins.select2.hasOwnProperty(this.name)) {
				Object.assign(option, appPlugins.select2[this.name]);
			}

			$this.wrap('<div class="position-relative w-100"></div>').select2(option);
		});
	}

	// Bootstrap Max Length
	if ($(form).find('.form-maxlength').length) {
		$(form).find('.form-maxlength').each(function () {
			$(this).maxlength({
				warningClass: 'label label-success bg-success text-white',
				limitReachedClass: 'label label-danger',
				separator: getLocale(' out of ', common.LOCALE),
				preText: getLocale('You typed ', common.LOCALE),
				postText: getLocale(' chars available', common.LOCALE),
				validate: true,
				threshold: +this.getAttribute('maxlength')
			});
		});
	}

	// textarea-autosize
	if($(form).find('.form-input_textarea-autosize').length) {
		$(form).find('.textarea-autosize').each(function() {
			autosize(this);
		})
	}

	if ($(form).find('.select2-repeater').length) {
		$(form).find('.select2-repeater').each(function () {
			var $this = $(this);
			$this.prepend('<option value="" disabled selected></option>');
			$this.wrap('<div class="position-relative"></div>').select2({
				allowClear: true,
				dropdownParent: $this.parent(),
				placeholder: $this.data('placeholder') // for dynamic placeholder
			});
		});
	}

	// form-repeater-jquery
	if($(form).find('[data-repeater-type="jquery"]').length) {
		$(form).find('[data-repeater-type="jquery"]').each(function() {
			var formType = $(this).data('form-type');
			var groupName = $(this).data('group-name');
			var repeater = common.FORM_REPEATER[groupName] = $(this).repeater({
				initEmpty: false,
				defaultValues: {},
				show: function () {
					var row = parseInt($(this).closest('[data-repeater-type]').attr('data-repeater-count'))+1;
					var wrap = this;
					var withList = false;
					var formControl = $(this).find('input, select, textarea');
					var formLabel = $(this).find('.form-label');
					formControl.each(function (i, item) {
						var id = `form_${formType}-${groupName}-` + (row-1) + '-' + $(item).data('group-field');

						// label
						if(item.nextElementSibling.tagName === 'LABEL') $(item.nextElementSibling).attr('for', id);

						// list-item-wrap
						if($(item).data('with-list')) {
							withList = true;
							$(wrap).find(`#${item.id}-list`).attr('id', id+'-list');
						}

						$(item).attr('id', id);
					});

					$(this).attr('data-row-index', row);
					$(this).closest('[data-repeater-type]').attr('data-repeater-count', row)

					window.dispatchEvent(
						new CustomEvent('addRepeaterItem', {
							bubbles : false,
							cancelable : true,
							composed : false,
							detail : {
								node : wrap
							},
						}),
					);

					// list-item-wrap
					if(withList) $(this).find('.form-list-item-wrap').addClass('d-none').empty();

					const cbName = camelize(`after_${groupName}_repeater_show`);
					if(typeof cbName === 'function') window[cbName](this);

					$(this).slideDown();
				},
				hide: function (deleteElement) {
					const repeater = this;
					const form = repeater.closest('form');
					const identifier = form[common.IDENTIFIER].value;
					if(!identifier) {
						$(this).slideUp(deleteElement);
					}else{
						const wrap = repeater.closest('[data-repeater-type]');
						if(repeaterId = wrap.getAttribute('data-repeater-id')) {
							if(repeater.querySelector(`[data-group-field="${repeaterId}"]`).value === ''){
								$(this).slideUp(deleteElement);
								return;
							}
						}

						Swal.fire({
							title: getLocale('Do you really want to delete?', common.LOCALE),
							text: getLocale('You can\'t undo this action', common.LOCALE),
							icon: 'warning',
							showCancelButton: true,
							confirmButtonText: getLocale('Delete', common.LOCALE),
							cancelButtonText: getLocale('Cancel', common.LOCALE),
							customClass: {
								confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
								cancelButton: 'btn btn-outline-secondary waves-effect'
							},
							buttonsStyling: false
						}).then(function (result) {
							if (result.isConfirmed) {
								deleteRepeater(repeater, deleteElement)
							}
						});
					}
				},
				ready: function(setIndexes) {
					console.log('Repeater is ready');
					setIndexes(); // If you need to set index
				},
				isFirstItemUndeletable: false
			});
		})
	}

	/**
	 * file list sortable
	 */
	if($('.form-list-item-wrap_sorter').length){
		$('.form-list-item-wrap_sorter').each((k, v) => {
			Sortable.create(v, {
				animation: 150,
				group: 'handleList',
				handle: '.drag-handle',
				swap: true,
				onEnd: function (event) {
					const newIndex = parseInt(event.newIndex)+1;
					const item = event.item;
					const articleId = item.getAttribute('data-article-id')
					const fileId = item.getAttribute('data-file-id')
					executeAjax({
						url : common.API_URI + '/reorder',
						headers : {
							'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
						},
						method : 'patch',
						data : {
							new_index : newIndex,
							article_id : articleId,
							file_id : fileId,
						},
						success: function(response) {
							showAlert({
								type: 'success',
								title: 'Complete',
								text: response.msg,
							});
						},
					});
				}
			});
		})
	}

	// [data-show-if-target]
	if($('[data-show-if-field]').length > 0) {
		$('[data-show-if-field]').each((k, v) => {
			const target = $(v).attr('data-show-if-field');
			if(target) $(v).closest('form').find(`.form-validation-unit:has([name="${target}"])`).addClass('d-none');
		});
	}

	// FlatPickr Initialization & Validation
	$(form).find('.flatpickr').each(function() {
		setFlatpickr(this);
	})

	// Cleave Initialization & Validation
	$(form).find('.cleave').each(function() {
		setCleave(this);
	})

	updateFormLifeCycle('preparePlugins', form)
}

/**
 * resetFrmInputs
 * - form mode 의 값에 관계없이 form input 들에 대한 처리
 * @param form
 * @param fields
 */
function resetFrmInputs(form, fields = []) {
	record = null;
	Object.keys(common.FORM_LIFECYCLE).forEach((v, k) => {
		if(!k) return;
		common.FORM_LIFECYCLE[v] = false;
	});

	form.querySelectorAll('input, textarea, select').forEach(function(node) {
		if(!isAttributeValueTrue(node, 'data-reset-value')) return;

		const value = fields.reduce((acc, curr) => {
			if(curr.field === node.name) acc = curr.default;
			return acc;
		}, '');

		switch (node.tagName) {
			case 'INPUT' :
				switch(node.type) {
					case 'radio' :
					case 'checkbox' :
						node.checked = false;
						break;
					case 'text' :
					case 'date' :
					case 'tel' :
					case 'number' :
					case 'hidden' :
					case 'password' :
						node.value = value;
						break;
					case 'file' :
						const newNode = node.cloneNode(true);
						node.parentNode.replaceChild(newNode, node);
				}
				break;
			case 'SELECT' :
				node.value = value;
				break;
			case 'TEXTAREA' :
				node.value = value;
				break;
		}

		if(node.type !== 'hidden' && !isAttributeValueTrue(node, 'data-detect-changed')){
			node.setAttribute('data-input-changed', 'false');
		}

		if(node.hasAttribute('data-original-value')){
			node.removeAttribute('data-original-value');
		}
	});

	// list 초기화
	fields.map(function(item) {
		if(list = document.querySelector('#'+item.id+'-list')) {
			list.innerHTML = '';
			list.classList.add('d-none');
		}
	})

	// repeater 초기화
	if($('[data-repeater-type="jquery"]').length) {
		$('[data-repeater-type="jquery"]').attr('data-repeater-count', 1);
	}

	// Event trigger
	updateFormLifeCycle('resetFrmInputs', form);
}

/**
 * readyFrmInputs
 * - form mode 에 따라 변경되는 input 처리
 * @param form
 * @param mode
 * @param fields
 */
function readyFrmInputs(form, mode, fields = []) {
	form.querySelectorAll('[data-view-mod]').forEach((node) => {
		const modList = node.getAttribute('data-view-mod').split('|');
		if(modList.includes(mode)){
			node.closest('.form-validation-unit').classList.remove('d-none');
		}else{
			node.closest('.form-validation-unit').classList.add('d-none');
		}
	});

	form.querySelectorAll('[data-editable="0"]').forEach((node) => {
		if(mode === 'edit') {
			node.setAttribute('readonly', 'readonly');
		}else{
			node.removeAttribute('readonly');
		}
	});

	if(form['_mode'] !== undefined) form['_mode'].value = mode;

	// Event trigger
	updateFormLifeCycle('readyFrmInputs', form, {
		mode : mode,
	});
}

/**
 * fetchFrmValues
 * - ajax 통신으로 server data를 fetch 및 data return
 * @param form
 * @param key
 * @returns {*}
 */
function fetchFrmValues(form = null, key = '', params = {}) {
	let data;

	executeAjax({
		async: false,
		url : common.API_URI + '/' + key + '?' + new URLSearchParams(common.API_PARAMS).toString(),
		data: {
			_mode : 'form',
			...params,
		},
		headers: {
			'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
		},
		success: function(response, textStatus, jqXHR) {
			data = isObject(response.data)?response.data:response.data[0];
			if(form) {
				record = data;

				// Event trigger
				updateFormLifeCycle('fetchFrmValues', form, {
					record : data,
				});
			}
		},
	});

	return data;
}

/**
 * applyFrmValues
 * - fetchFrmValues 로부터 전달받은 데이터를 각 input 에 입력
 * @param form
 * @param data
 * @param fields
 */
function applyFrmValues(form, data, fields = []) {
	if(!fields.length) return;

	// 단순 복제로 원본 fields object(common.FORM_DATA)에 대해서도
	// 변경이 반영되는 것을 방지하기 위해 깊은 복제 실행.
	const cloneFields = structuredClone(fields);

	const initGroupProperties = () => {
		return {
			inputs : [],
			selects : [],
			checkboxes : [],
			radios : [],
			textareas : [],
			excepts : [],
			files : [],
			customs : [],
		};
	};
	const groups = {
		basic : initGroupProperties(),
	};
	const groupAttrs = {
		basic : {
			group_name : 'basic',
		}
	};

	/**
	 * classify cloneFields into groups
	 */
	cloneFields.forEach((item) => {
		if(item.group && !groups.hasOwnProperty(item.group)) {
			groups[item.group] = initGroupProperties();
			groupAttrs[item.group] = Object.assign(item.group_attributes, {
				group_name : item.group,
			});
		}
		const groupName = item.group ? item.group : 'basic';
		if(item.category === 'custom') {
			groups[groupName].customs.push(item.field);
		}else{
			switch (item.type) {
				case 'select' : groups[groupName].selects.push(item.field); break;
				case 'checkbox' : groups[groupName].checkboxes.push(item.field); break;
				case 'radio' : groups[groupName].radios.push(item.field); break;
				case 'textarea' : groups[groupName].textareas.push(item.field); break;
				case 'file' : groups[groupName].files.push(item.field); break;
				case 'custom' : groups[groupName].customs.push(item.field); break;
				default :
					if(['password'].includes(item.type)) {
						groups[groupName].excepts.push(item.field);
					}else{
						groups[groupName].inputs.push(item.field);
					}
			}
		}
	});

	Object.keys(groups).forEach((groupName, i) => {
		const dto = groups[groupName];

		// group_repeater row 생성
		if(groupAttrs[groupName].group_repeater) {
			// group_repeater type jquery
			if(groupAttrs[groupName].repeater_type === 'jquery' && $('[data-repeater-type="jquery"]').length) {
				const repeater = document.querySelector(`[data-repeater-type="jquery"][data-group-name="${groupName}"]`);
				const rowCount = parseInt(repeater.getAttribute('data-repeater-count'));
				for(let i = rowCount+1; i <= data[groupName].length; i++) repeater.querySelector('[data-repeater-create]').click();
				repeater.setAttribute('data-repeater-count', data[groupName].length === 0 ? 1 : data[groupName].length);
			}
		}

		if(typeof window[`applyFrmValues${pascalize(groupName)}`] === 'function') {
			window[`applyFrmValues${pascalize(groupName)}`](form, data[groupName], groupAttrs[groupName], cloneFields, dto);
		}else{
			Object.keys(dto).map((category) => {
				if(dto[category].length > 0) {
					if(groupName === 'basic') {
						dto[category].forEach((fieldName) => {
							applyFrmValuesByCategory(category, groupAttrs[groupName], fieldName, cloneFields, form, data);
						});
					}else{
						if(groupAttrs[groupName].envelope_name) {
							if(!data.hasOwnProperty(groupName)) return;

							let frmData = data[groupName];
							if(groupAttrs[groupName].group_repeater) {
								// 데이터 개수에 따라, field의 id, name 을 data index 의 값으로 대체해야 함.
								for(let dataIndex = 0; dataIndex < frmData.length; dataIndex++) {
									Object.keys(frmData[dataIndex]).map((fieldName) => {
										if(!dto[category].includes(fieldName)) return;
										if(common.IDENTIFIER) frmData[dataIndex][common.IDENTIFIER] = data[common.IDENTIFIER];
										applyFrmValuesByCategory(category, groupAttrs[groupName], fieldName, cloneFields, form, frmData[dataIndex], dataIndex);
									});
								}
							}else{
								if(common.IDENTIFIER) frmData[common.IDENTIFIER] = data[common.IDENTIFIER];
								dto[category].forEach((fieldName) => {
									applyFrmValuesByCategory(category, groupAttrs[groupName], fieldName, cloneFields, form, frmData);
								});
							}
						}else{
							dto[category].forEach((fieldName) => {
								applyFrmValuesByCategory(category, groupAttrs[groupName], fieldName, cloneFields, form, data);
							});
						}
					}
				}
			});
		}
	});
}

function getFrmInputDto(groupAttrs, field, dataIndex = 0) {
	const groupName = groupAttrs.group_name === 'basic'?'':groupAttrs.group_name;

	if(!groupName) {
		return field;
	}else{
		if(!groupAttrs.group_repeater) {
			return field;
		}else{
			let regexId, regexName;
			if(groupAttrs.envelope_name) {
				regexId = new RegExp(`(-${groupName}-)\\d+(-${field.field})`);
				field.id = field.id.replace(regexId, `$1${dataIndex}$2`);

				regexName = new RegExp(`(${groupName}\\[)\\d+(\\]\\[${field.field}\\])`);
				field.name = field.name.replace(regexName, `$1${dataIndex}$2`);
			}else{
				regexId = new RegExp(`(-${field.field}-)\\d+`);
				field.id = field.id.replace(regex, `$1${dataIndex}`);

				regexName = new RegExp(`(${listName}\\[)\\d+(\\])`);
				field.name = field.name.replace(regexName, `$1${dataIndex}$2`);
			}
			return field;
		}
	}
}

function applyFrmValuesByCategory(category, groupAttr, fieldName, fields, form, data, dataIndex = 0) {
	if(!data) return;
	const groupName = groupAttr.group_name === 'basic'?'':groupAttr.group_name;
	const fieldIndex = fields.findIndex(item => item.field === fieldName && item.group === groupName);
	if(fieldIndex < 0) return;
	const field = getFrmInputDto(groupAttr, fields[fieldIndex], dataIndex);

	const name = field.name;
	const id = field.id;

	switch (category) {
		case 'inputs' :
			switch (field.subtype) {
				default:
					if(form[name] && data[fieldName] && data[fieldName] !== '0000-00-00') form[name].value = data[fieldName];
			}
			break;
		case 'selects' :
			switch (field.subtype) {
				default:
					if(form[name] && data[fieldName]) {
						form[name].value = data[fieldName];
						form[name].setAttribute('data-original-value', data[fieldName]);
						$(form[name]).val(data[fieldName]).trigger('change');
					}
					break;
			}
			break;
		case 'checkboxes' :
			switch (field.subtype) {
				case 'single' :
					if(!data[fieldName]) return;
					form.querySelectorAll(`[name="${name}"]`).forEach((input) => {
						if(input.value == data[fieldName]) input.checked = true;
					});
					break;
				default:
					if(!data[fieldName]) return;
					form.querySelectorAll(`[name="${name}[]"]`).forEach((input) => {
						if(typeof data[fieldName] === 'string') {
							if(data[fieldName].split(',').includes(input.value)) input.checked = true;
						}else{
							if(data[fieldName].includes(input.value)) input.checked = true;
						}
					});
			}
			break;
		case 'radios' :
			switch (field.subtype) {
				default:
					if(!data[fieldName]) return;
					form.querySelectorAll(`[name="${name}"]`).forEach((input) => {
						console.log(input)
						if(input.value == data[fieldName]) input.checked = true;
					});
			}
			break;
		case 'textareas' :
			switch (field.subtype) {
				default:
					if(form[name] && data[fieldName]) form[name].value = data[fieldName];
			}
			break;
		case 'files' :
			switch (field.subtype) {
				case 'basic' :
				case 'thumbnail' :
				case 'single' :
				case 'multiple' :
				case 'readonly' :
					// setFormListItem(`#${id}-list`, data[fieldName], field);
					break;
				default :
					if(data[fieldName] === undefined) console.warn(`applyFrmValues : data doesn't have '${name}'.`);
					if(form[name] === undefined) console.warn(`applyFrmValues : form doesn't have '${name}'.`);
					if(data[fieldName] !== undefined && form[name] !== undefined)
						form[name].value = data[fieldName];
					break;
			}
			break;
		case 'customs' :
			switch (field.subtype) {
				case 'youtube' :
					setFormListItem(`#${id}-list`, data, field);
					break;
				case 'quill' :
					document.getElementById(`${id}-quill`).innerHTML = data[fieldName];
					form[name].value = data[fieldName];
					break;
				case 'reply_list' :
					// setFormListItem(`#${id}`, data[fieldName], field);
					break;
				case 'dropzone-full' :
					setTimeout(() => {
						if(data[fieldName] === undefined) console.warn(`applyFrmValues : data doesn't have '${name}'.`);
						if(data[fieldName]) {
							const inst = Dropzone.instances.find((item) => item.element.getAttribute('data-field') === 'thumbnail');
							const mockFile = {
								name: data[fieldName][0].file_name,
								size: data[fieldName][0].file_size,
								accepted: true,
							};
							inst.displayExistingFile(mockFile, data[fieldName][0].file_link)
							inst.mockup = true;
						}
					},0);
					break;
				default :
					if(data[fieldName] === undefined) console.warn(`applyFrmValues : data doesn't have '${name}'.`);
					if(form[name] === undefined) console.warn(`applyFrmValues : form doesn't have '${name}'.`);
					if(data[fieldName] !== undefined && form[name] !== undefined)
						form[name].value = data[fieldName];
					break;
			}
			break;
	}

	if(field.form_attributes.with_btn) {
		if(field.form_attributes.btn_type === 'dup_check') {
			form[name].setAttribute('data-original-value', data[fieldName]);
		}
	}

	if(field.form_attributes.with_list) {
		setFormListItem(`#${id}-list`, data, field);
	}

	if(field.form_attributes.show_if_field) {
		const target = field.form_attributes.show_if_field;
		if(data[fieldName] == field.form_attributes.show_if_value) {
			form[target].closest('.form-validation-unit').classList.remove('d-none');
		}else{
			form[target].closest('.form-validation-unit').classList.add('d-none');
		}
	}
}

function refreshPlugins() {
	// Select Picker
	if($('.selectpicker').length) {
		$('.selectpicker').selectpicker('refresh');
	}

	// Select2
	if($('.select2').length) $('.select2').trigger('change');
	if($('.select2-repeater').length) $('.select2-repeater').trigger('change');

	// textarea-autosize
	if($('.textarea-autosize').length) {
		$('.textarea-autosize').each(function() {
			if(this.scrollHeight > this.clientHeight) {
				//textarea height 확장
				this.style.height = this.scrollHeight + "px";
			}else{
				//textarea height 축소
				this.style.removeProperty('height');
				autosize(this);
				this.style.height = this.scrollHeight + "px";
			}
		})
	}

	// textarea-quill
	if($('.textarea-quill').length) {
		$('.textarea-quill').each(function() {
			editors[this.id] = new Quill(`#${this.id}`, {
				bounds: `#${this.id}`,
				placeholder: 'Type Something...',
				modules: {
					formula: true,
					toolbar: toolbarDefault.quill
				},
				theme: 'snow'
			});

			$(`#${this.id}`).on('keydown', function(e) {
				$(`[data-textarea-id="${this.id}"]`).val(editors[this.id].root.innerHTML)
			});
		})
	}

	$('.form-list-item-wrap').each(function() {
		if(!this.children.length) this.classList.add('d-none');
	});

	updateFormLifeCycle('refreshPlugins');
}

function setFormListItem(selector, data, field) {
	if(!isValidSelector(selector) || document.querySelector(selector) === null) return;

	let html = '';
	const key = field.form_attributes.list_field ?? field.field;
	if(!isEmpty(data[key])) {
		let list = [];
		isArray(data[key]) ? list = data[key] : list.push(data[key]);
		list.forEach((item) => {
			if(common.IDENTIFIER && data.hasOwnProperty(common.IDENTIFIER)) item[common.IDENTIFIER] = data[common.IDENTIFIER];
			const identifier = common.IDENTIFIER?data[common.IDENTIFIER]:null;
			switch (field.form_attributes.list_type ?? field.subtype) {
				case 'thumbnail' :
					html += setFormListItemThumbnail(field, item, identifier);
					break;
				case 'youtube' :
					html += setFormListItemYoutube(field, item, identifier);
					break;
				case 'reply_list' :
					html += setFormListItemReplyList(field, item, identifier);
					break;
				default :
					html += setFormListItemFile(field, item, identifier);
					break;
			}
		});
		document.querySelector(selector).classList.remove('d-none');
		if(field.subtype === 'readonly') document.querySelector(selector).closest('.form-validation-unit').classList.remove('d-none');
	}else{
		document.querySelector(selector).classList.add('d-none');
		if(field.subtype === 'readonly') document.querySelector(selector).closest('.form-validation-unit').classList.add('d-none');
	}

	document.querySelector(selector).innerHTML = html;
}

function setFormListItemFile(field, item, identifier = '') {
	const url = location.origin+location.pathname;
	const fullItem = JSON.stringify(item).replace(/"/g, "'");
	const articleId = item.article_id ?? '';
	const fileId = item.file_id;
	if(field.form_attributes.hasOwnProperty('list_sorter') && field.form_attributes.list_sorter) {
		let output = `
            <li class="form-list-item list-group-item d-flex justify-content-between align-items-center px-2" data-identifier-val="${identifier}" data-full-item="${fullItem}" data-article-id="${articleId}" data-file-id="${fileId}">
                <div class="d-flex justify-content-between align-items-center pe-4">
                    <i class="drag-handle cursor-move ri-menu-line align-text-bottom me-2"></i>
                    <span class="not-draggable">${item.orig_name}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <button class="btn btn-primary waves-effect p-1" type="button" onclick="downloadFile(${fileId})">
                        <i class="ri-file-download-line ri-16px align-middle"></i>
                    </button>
        `;
		if(field.form_attributes.list_delete.length > 0){
			output += `
                    <button class="btn btn-danger waves-effect p-1 ms-1" type="button" onclick="deleteFile(this, '${field.form_attributes.list_delete}')">
                        <i class="ri-close-line ri-16px align-middle"></i>
                    </button>
            `;
		}
		output += `
                </div>
            </li>
        `;
		return output;
	}else{
		return `
        <div class="form-list-item d-flex align-items-center" data-identifier-val="${identifier}" data-full-item="${fullItem}">
            <div class="badge text-body text-truncate">
                <a href="${url}/downloader/${fileId}">
                    <i class="ri-file-download-fill ri-16px align-middle"></i>
                    <span class="h6 mb-0 align-middle">${item.orig_name}</span>
                </a>
            </div>
        </div>
    `;
	}
}

function setFormListItemThumbnail(field, item, identifier = '') {
	const url = location.origin+location.pathname;
	const fullItem = JSON.stringify(item).replace(/"/g, "'");
	return `
        <div class="form-list-item d-flex align-items-start flex-column justify-content-center" data-identifier-val="${identifier}" data-full-item="${fullItem}">
            <div class="d-flex align-items-center justify-content-between w-100 mb-2">
                <div class="badge text-body text-truncate">
                    <a href="${url}/downloader/${item.file_id}">
                        <i class="ri-file-download-fill ri-16px align-middle"></i>
                        <span class="h6 mb-0 align-middle">${item.orig_name}</span>
                    </a>
                </div>
                <button class="btn btn-danger waves-effect p-1" type="button" onclick="deleteFile(this, 'thumbnail')">
                    <i class="ri-close-line ri-16px align-middle"></i>
                </button>
            </div>
            <div class="border rounded-3 overflow-hidden">
                <img src="${item.file_link}" alt="${item.orig_name}" class="mw-100 not-draggable" draggable="false">
            </div>
        </div>
    `;
}

function setFormListItemPreview(field, item, identifier = '') {
	const url = location.origin+location.pathname;
	const fullItem = JSON.stringify(item).replace(/"/g, "'");
	return `
        <div class="form-list-item d-flex align-items-center" data-identifier-val="${identifier}" data-full-item="${fullItem}">
            <div class="badge text-body text-truncate">
                <a href="${url}/downloader/${item.file_id}">
                    <i class="ri-file-download-fill ri-16px align-middle"></i>
                    <span class="h6 mb-0 align-middle">${item.orig_name}</span>
                </a>
            </div>
        </div>
    `;
}

function setFormListItemYoutube(field, item, identifier = '') {
	const url = location.origin+location.pathname;
	const fullItem = JSON.stringify(item).replace(/"/g, "'");
	return `
        <div class="d-flex align-items-center" data-identifier-val="${identifier}" data-full-item="${fullItem}">
            <div class="badge text-body text-truncate">
                <a href="#" role="button" data-bs-toggle="modal" data-bs-target="#youTubeModal" data-value="${item}">
                    <i class="ri-link mt-0 ri-16px align-middle"></i>
                    <span class="h6 mb-0 align-middle">https://youtu.be/${item}</span>
                </a>
            </div>
        </div>
    `;
}

function setFormListItemReplyList(field, item, identifier = '') {
	const url = location.origin+location.pathname;
	const fullItem = JSON.stringify(item).replace(/"/g, "'");
	return `
        <li class="form-list-item mb-1 ps-0 p-2" data-identifier-val="${identifier}" data-full-item="${fullItem}">
            <p class="h6 mb-1">${item.content}</p>
            <p class="text-end mb-0">${item.created_id} ${item.created_dt}</p>
        </li>
    `;
}
