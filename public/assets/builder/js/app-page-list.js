'use strict';

let fv, offCanvasEl;

// Datatable (jquery)
$(function () {
	let borderColor, bodyBg, headingColor;

	if (isDarkStyle) {
		borderColor = config.colors_dark.borderColor;
		bodyBg = config.colors_dark.bodyBg;
		headingColor = config.colors_dark.headingColor;
	} else {
		borderColor = config.colors.borderColor;
		bodyBg = config.colors.bodyBg;
		headingColor = config.colors.headingColor;
	}

	// Variable declaration for table
	var dt_table = $('.datatables-records'),
		statusObj = {
			1: { title: 'Pending', class: 'bg-label-warning' },
			2: { title: 'Active', class: 'bg-label-success' },
			3: { title: 'Inactive', class: 'bg-label-secondary' }
		};

	// Datatable
	if(!dt_table.length) throw new Error(`dt_table is not defined!`);
	if(!common.LIST_COLUMNS.length) throw new Error(`check common LIST_COLUMNS.`);
	if(dt_table.find('thead th').length !== 1+common.LIST_CHEKBOX+common.LIST_COLUMNS.length)
		throw new Error(`th and LIST_COLUMNS length are not matched.`);

	var dt = dt_table.DataTable({
		scrollX: true,
		scrollCollapse: true,
		processing: true,
		serverSide: true,
		ajax: getAjaxOptions({
			url: common.API_URI,
			headers: {
				'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
			},
			data: function(data) {
				const req = {
					_mode : 'list',
					...common.API_PARAMS,
					...{
						format: 'datatable',
						draw: data.draw,
						pageNo: Math.floor(data.start / data.length) ,
						limit: data.length,
						searchWord: data.search.value || '',
						searchCategory: data.search.category || '',
					},
				};

				if($('#formFilter').length) {
					const filters = {
						where : {},
						like : {
							field : null,
							value : null,
						},
						date : {
							start_date : null,
							end_date : null,
							on_date : null,
						}
					};

					if($('#formFilter').find('[name="_onloaded"]').val() === '1') {
						filters.like.field = $('#formFilter').find('[name="like[field]"]').val() ?? null;
						filters.like.value = $('#formFilter').find('[name="like[value]"]').val() ?? null;
						filters.like.start_date = $('#formFilter').find('[name="date[start_date]"]').val() ?? null;
						filters.like.end_date = $('#formFilter').find('[name="date[end_date]"]').val() ?? null;
						filters.like.on_date = $('#formFilter').find('[name="date[on_date]"]').val() ?? null;

						$('#formFilter').find('[name^="where"]').each(function() {
							const match = this.name.match(/\[(.*?)\]/);
							if (match) {
								const key = match[1];
								filters.where[key] = this.value;
							}
						});
					}

					req.filters = filters;
				}

				return req;
			},
			complete: function(data) {
				// console.log(data.responseJSON)
				// console.table(data.responseJSON.data)
			},
		}),
		columns: [
			// columns according to JSON
			{ data: null },
			...(common.LIST_CHEKBOX
				? [{ data: null }]
				: []),
			...common.LIST_COLUMNS.map(function (column) {
				return {
					data : column.field,
					title : getLocale(column.label, common.LOCALE),
				}
			}),
		],
		columnDefs: [
			{
				// For Responsive
				className: 'control',
				searchable: false,
				orderable: false,
				responsivePriority: 2,
				targets: 0,
				render: function (data, type, full, meta) {
					return '';
				}
			},
			...(common.LIST_CHEKBOX
				? [
					{
						// For Checkboxes
						targets: 1,
						orderable: false,
						render: function () {
							return '<input type="checkbox" class="dt-checkboxes form-check-input">';
						},
						checkboxes: {
							selectAllRender: '<input type="checkbox" class="form-check-input">'
						}
					},
				]
				: []),
			// ...fields,
			...common.LIST_COLUMNS.map(function (column, index) {
				switch (column.format) {
					case 'row_num' : // Row Num
						return {
							targets: 1+common.LIST_CHEKBOX,
							searchable: false,
							orderable: false,
							render: function (data, type, full, meta) {
								return meta.row + meta.settings._iDisplayStart + 1;
							}
						};
					case 'actions' : // Actions
						return {
							targets: 1+common.LIST_CHEKBOX+common.LIST_COLUMNS.length-1,
							searchable: false,
							orderable: false,
							render: function (data, type, full, meta) {
								return getListActions(common.LIST_ACTIONS, common.IDENTIFIER?full[common.IDENTIFIER]:'');
							}
						}
					case 'select' :
						return {
							targets: 1+common.LIST_CHEKBOX+index,
							searchable: false,
							orderable: false,
							render: function(data, type, full, meta) {
								return renderSelectColumn(data, type, full, meta, column)
							},
						}
					default :
						return {
							searchable: true,
							orderable: false,
							targets: 1+common.LIST_CHEKBOX+index,
							render: function (data, type, full, meta) {
								if(column.render && column.render.callback && typeof window[`${column.render.callback}`] !== 'function'){
									console.warn(`DataTable : '${column.render.callback}' render function is not defined.`);
								}

								if(column.render && column.render.callback && typeof window[`${column.render.callback}`] === 'function') {
									// callback 이 정의되어있을 경우
									return window[column.render.callback](data, type, full, meta, column, column.render.params??null);
								}else if(typeof window[`renderColumn${pascalize(column.field)}`] === 'function') {
									// page 별 custom js 파일에 render func 가 정의된 경우
									return window[`renderColumn${pascalize(column.field)}`](data, type, full, meta, column);
								}else {
									// if(column.format === 'button') {
									//     return renderButtonColumn(data, type, full, meta, column);
									// }else if{
									// if(full[column.field]) {
									return renderColumn(data, type, full, meta, column);
									// }else{
									//     console.warn(`dtTable : ${column.field} data is missing !!`);return '-';
									// }
								}
							}
						};
				}
			}),
		],
		order: [[1+common.LIST_CHEKBOX, 'desc']],
		dom:
			'<"card-header d-flex border-top rounded-0 flex-wrap pb-md-0 pt-0 justify-content-end"' +
			// '<"d-flex justify-content-start align-items-center me-5 ms-n2"<"search-category-wrap me-2">f>' +
			// '<"me-5 ms-n2"f>' +
			'<"d-flex justify-content-start justify-content-md-end align-items-baseline"<"dt-action-buttons d-flex align-items-start align-items-md-center justify-content-sm-center gap-4"lB>>' +
			'>t' +
			'<"row mx-1"' +
			'<"col-sm-12 col-md-6"i>' +
			'<"col-sm-12 col-md-6"p>' +
			'>',
		language: {
			sLengthMenu: '_MENU_',
			search: '',
			searchPlaceholder: getLocale('Search', common.LOCALE),
			info: '검색결과 총 _TOTAL_ 개 데이터 중 _START_ ~ _END_ 표시'
		},
		// Buttons with Dropdown
		buttons: getListButtons(),
		// For responsive popup
		responsive: {
			details: {
				display: $.fn.dataTable.Responsive.display.modal({
					header: function (row) {
						return 'Details of ' + common.TITLE;
					}
				}),
				type: 'column',
				renderer: function (api, rowIdx, columns) {
					var data = $.map(columns, function (col, i) {
						return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
							? '<tr data-dt-row="' +
							col.rowIndex +
							'" data-dt-column="' +
							col.columnIndex +
							'">' +
							'<td>' +
							col.title +
							':' +
							'</td> ' +
							'<td>' +
							col.data +
							'</td>' +
							'</tr>'
							: '';
					}).join('');

					return data ? $('<table class="table"/><tbody />').append(data) : false;
				}
			}
		},
		preDrawCallback: function(settings) {
			// console.log('preDrawCallback', settings)
			// $('<div class="loading">Loading</div>').appendTo('body');
			if(appPlugins.list.datatable.preDrawCallback !== null && typeof appPlugins.list.datatable.preDrawCallback === 'function'){
				appPlugins.list.datatable.preDrawCallback(settings)
			}
		},
		initComplete: function (settings, json) {
			// ajax 옵션을 사용해서 테이블이 완전히 초기화되고 데이터가 로드되고 그려지는 시점
			// console.log('initComplete', settings);
			// $('div.loading').remove();
			if(appPlugins.list.datatable.initComplete !== null && typeof appPlugins.list.datatable.initComplete === 'function'){
				appPlugins.list.datatable.initComplete(settings, json)
			}

			if($('#formFilter').length > 0) {
				$('#formFilter').on('preparePlugins', (e) => {
					const form = document.getElementById(e.detail.formSelector);
					if(form !== null && form.querySelector('[name="_onloaded"]') !== undefined)
						form.querySelector('[name="_onloaded"]').value = 1;
				});

				preparePlugins(document.querySelector('#formFilter'));

				$('.form-type-filter').find('.btn-search').on('click', function(e) {
					dt.ajax.reload();
				});

				$('.form-type-filter').find('.btn-reset').on('click', function(e) {
					e.preventDefault();
					$('.form-type-filter')[0].reset();
					if($('.form-type-filter').find('.form-select').length) {
						$('.form-type-filter').find('.form-select').trigger('change')
					}
					dt.ajax.reload();
				});
			}
		},
		drawCallback: function(settings) {
			// console.log('drawCallback', settings)
			// 테이블의 draw 이벤트가 발생할 때마다 취해야 하는 action 을 실행
			if(appPlugins.list.datatable.drawCallback !== null && typeof appPlugins.list.datatable.drawCallback === 'function'){
				appPlugins.list.datatable.drawCallback(settings)
			}

			if($(this).find('.datatable-selectpicker').length > 0){
				$(this).find('.datatable-selectpicker').selectpicker({
					width: 'fit',
					container: 'body',
				});
			}
		},
		rowCallback: function (row, data, displayNum, displayIndex, dataIndex) {
			// data[3] -> Age 컬럼 값이 40 이상이면 행 색깔 변경
			if(appPlugins.list.datatable.rowCallback !== null && typeof appPlugins.list.datatable.rowCallback === 'function'){
				appPlugins.list.datatable.rowCallback(row, data, displayNum, displayIndex, dataIndex)
			}
		}
	});

	$('.dataTables_wrapper').on('click', '.view-record', function() {
		if(!common.IDENTIFIER) throw new Error(`Identifier is not defined`);
		if(common.PAGE_VIEW_URI) {
			location.href = common.PAGE_VIEW_URI + '/' + $(this).data('id');
		}else{
			openViewModal($(this).data('id'));
		}
	});

	$('.dataTables_wrapper tbody').on('click', '.delete-record', function () {
		if(!common.IDENTIFIER) throw new Error(`Identifier is not defined`);
		const id = $(this).data('id');
		deleteData(id, {
			callback: dt.ajax.reload,
			params: [null, false]
		});
	});

	$('#filter-search_word').on('keydown', function(e) {
		if (e.key === 'Enter') {
			e.preventDefault();
			console.log('Enter pressed! Value:', this.value);
			// 여기에 원하는 함수 호출 등 처리
			if($('#filter-search_category').val()) $('.btn-search').click()
		}
	});

	if(common.SIDE_FORM) {
		const formSelector = '#formRecord';
		const offCanvasElement = document.querySelector('#offcanvasRecord');
		if(offCanvasElement === null) throw new Error(`offCanvasElement is not exist`);
		offCanvasEl = new bootstrap.Offcanvas(offCanvasElement);

		offCanvasElement.addEventListener('show.bs.offcanvas', function(e) {
			console.log('offcanvas show');
			refreshPlugins();
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

			if ($('[data-repeater-item]').length) {
				$('[data-repeater-item]').each(function (i, v) {
					if(i > 0) $(v).remove();
				});
			}
		});

		const formRecord = document.querySelector(formSelector);
		if(formRecord === null) throw new Error(`formRecord is not exist`);
		preparePlugins(formRecord);

		$('.dataTables_wrapper').on('click', '.edit-record', function() {
			if(!common.IDENTIFIER) throw new Error(`Identifier is not defined`);
			readyFrmInputs(formRecord, 'edit', common.FORM_DATA);
			fetchFrmValues(document.querySelector(formSelector), $(this).data('id'));
		});

		formRecord.addEventListener('readyFrmInputs', (e) => {
			offCanvasEl.show();
		});

		formRecord.addEventListener("fetchFrmValues", (e) => {
			readyFrmInputs(formRecord, 'edit', common.FORM_DATA);
			applyFrmValues(formRecord, record, common.FORM_DATA);
			refreshPlugins();
			offCanvasEl.show();
		});

		formRecord.addEventListener('transFrmValues', (e) => {
			offCanvasEl.hide();
		});

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
					showAlert({
						type: 'success',
						title: 'Complete',
						text: formRecord['_mode'].value === 'edit' ? 'Your Data Is Updated' : 'Registered Successfully',
						callback: dt.ajax.reload,
						params: [null, false]
					});
					updateFormLifeCycle('transFrmValues', formRecord);
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
	}else{
		$('.dataTables_wrapper').on('click', '.edit-record', function() {
			if(!common.IDENTIFIER) throw new Error(`Identifier is not defined`);
			if(!common.PAGE_EDIT_URI.length) throw new Error(`PAGE_EDIT_URI is not defined`);
			location.href = common.PAGE_EDIT_URI + '/' + $(this).data('id');
		});

		$('.dataTables_wrapper').on('click', '.view-record', function() {
			if(!common.IDENTIFIER) throw new Error(`Identifier is not defined`);
			if(!common.PAGE_VIEW_URI.length) throw new Error(`PAGE_VIEW_URI is not defined`);
			location.href = common.PAGE_VIEW_URI + '/' + $(this).data('id');
		});
	}
});

function renderColumn(data, type, full, meta, column) {
	let wrap;
	switch (column.format) {
		case 'button':
			wrap = document.createElement('button');
			wrap.classList.add('btn', 'btn-sm', 'btn-info', 'waves-effect', 'waves-light', 'pe-3', 'ps-3');
			break;
		case 'text':
		case 'icon':
		case 'img':
		default :
			wrap = document.createElement('span');
			wrap.classList.add('d-inline-block')
			break;
	}

	// class
	if(column.classes.length)
		for(const className of classed) wrap.classList.add(className);

	// inner
	let inner;
	if(['recent_dt', 'created_dt', 'updated_dt'].includes(column.field)) {
		if(!data) return '-';
		const dateObj = new Date(data);
		inner = dateObj.getFullYear().toString() + '-' + (dateObj.getMonth()+1).toString().padStart(2, '0') + '-' + dateObj.getDate().toString().padStart(2, '0');
	}else{
		if(column.onclick.hasOwnProperty('kind')
			&& column.onclick.kind === 'download'
			&& !column.text
		) {
			column.text = 'Download';
		}

		switch (column.format) {
			case 'text':
				inner = column.text?getLocale(column.text, common.LOCALE):data;
				break;
			case 'button':
				if(['popup', 'redirect', 'download'].includes(column.onclick.kind) && (data === null || data.length === 0)) return '-';
				inner = column.text?getLocale(column.text, common.LOCALE):getLocale(column.field, common.LOCALE);
				break;
			case 'icon':
				inner = `<i class="${column.icon}"></i>`;
				break;
			case 'img':
				if(data === null || !data.length || !data[0].hasOwnProperty('file_link')) return '-';
				inner = `<img class="img-thumbnail d-inline rounded-2 overflow-hidden" src="${data[0].file_link}">`;
				wrap.setAttribute('data-bs-content', inner);
				break;
			case 'select':
				inner = `<select onchange="">`;
				inner += `</select>`;
				break;
			default :
				inner = '-';
		}
	}

	// return
	if(isArray(full[column.field])) {
		const html = full[column.field].reduce((acc, curr) => {
			return acc += renderColumnHTML(curr, full, column, wrap, inner);
		}, '');
		return `<div>${html}</div>`;
	}else{
		return renderColumnHTML(data, full, column, wrap, inner);
	}
}

function renderColumnHTML(data, full, column, wrap, inner) {
	// attrs
	let value;
	if(data === null || data === undefined) {
		value = '';
	}else{
		value = typeof data === 'object'?JSON.stringify(data):data;
	}

	const attrs = {
		...Object.fromEntries(
			Object.entries({
				identifier : full[common.IDENTIFIER],
				value : value,
			}).map(([key, value]) => [`data-${key}`, value])
		)
	};

	if(Object.keys(column.onclick).length) {
		wrap.classList.add('cursor-pointer');
		attrs.onclick = getColumnOnclick(data, full, column);

		if(!column.onclick.hasOwnProperty('noValue')) {
			column.onclick.noValue = false;
		}

		if(!column.onclick.noValue && value === '') inner = '';

		if(column.onclick.kind === 'bs') {
			if(column.onclick.noValue || value !== '') {
				if(!column.onclick.hasOwnProperty('attrs')) column.onclick.attrs = {};
				if(Object.keys(column.onclick.attrs).length) {
					Object.entries(column.onclick.attrs).map(([key, value]) => attrs[`data-bs-${key}`] = value )
				}
			}
		}

		if(column.onclick.kind === 'view' && column.format !== 'button') wrap.classList.add('text-primary', 'text-decoration-underline')
	}

	Object.entries(attrs).map(([key, value]) => wrap.setAttribute(key, value));

	// return
	wrap.innerHTML = inner;
	return wrap.outerHTML;
}

function renderSelectColumn(data, type, full, meta, column) {
	const wrap = document.createElement('div');
	wrap.classList.add('bootstrap-select', 'w-px-100')
	const select = document.createElement('select');
	select.classList.add('datatable-selectpicker','w-px-100')

	if(column.hasOwnProperty('options')) {
		for(const value of Object.keys(column.options)){
			const option = document.createElement('option');
			option.value = value;
			option.innerText = column.options[value];
			if(data === value) option.setAttribute('selected', 'selected')
			select.appendChild(option);
		}
	}

	let funcName = 'afterSelectColumnChange';
	if(column.hasOwnProperty('onChange') || column.hasOwnProperty('onchange')) {
		const key = column.hasOwnProperty('onChange')?'onChange':'onchange';
		funcName = column[key];
	}
	select.setAttribute('onchange', `${funcName}(${full[common.IDENTIFIER]}, '${column.field}', this.value)`);

	wrap.appendChild(select)
	return wrap.outerHTML;
}

function renderButtonColumn(data, type, full, meta, column) {
	const wrap = document.createElement('button');
	wrap.classList.add('btn', 'btn-info', 'waves-effect', 'waves-light', 'pe-3', 'ps-3');

	// class
	if(column.classes.length)
		for(const className of classed) wrap.classList.add(className);

	if(column.onclick.hasOwnProperty('kind')
		&& column.onclick.kind === 'download'
		&& !column.text
	) {
		column.text = 'Download';
	}

	const inner = column.text?getLocale(column.text, common.LOCALE):getLocale(column.field, common.LOCALE);

	// return
	if(isArray(full[column.field])) {
		const html = full[column.field].reduce((acc, curr) => {
			return acc += renderColumnHTML(curr, full, column, wrap, inner);
		}, '');
		return `<div>${html}</div>`;
	}else{
		return renderColumnHTML(data, full, column, wrap, inner);
	}
}

function getColumnOnclick(data, full, column) {
	let onClick = '';
	if(column.hasOwnProperty('onClick') || column.hasOwnProperty('onclick')) {
		const key = column.hasOwnProperty('onClick')?'onClick':'onclick';
		if(!column[key].kind) throw new Error(`getColumnOnclick : onclick kind is not defined. (${column.field})`);

		switch (column[key].kind) {
			case 'view' :
				let uri;
				if(column[key].params.length && column[key].params.hasOwnProperty('uri')) {
					uri = column[key].params.uri;
				}else{
					if(common.PAGE_VIEW_URI) uri = common.PAGE_VIEW_URI;
				}

				if(uri) {
					if(column[key].attrs.target === '_blank'){
						return `window.open('${uri}/${full[common.IDENTIFIER]}', "_blank")`
					}else{
						return `location.href="${uri}/${full[common.IDENTIFIER]}"`;
					}
				}else{
					return `openViewModal(${full[common.IDENTIFIER]})`;
				}
			case 'popup' :
				break;
			case 'redirect' :
				if(column[key].hasOwnProperty('params') && column[key].attrs.hasOwnProperty('target'))
					if(column[key].attrs.target === '_blank') return `window.open('${data}', "_blank")`;
				return `location.href="${data}"`;
			case 'download' :
				if(data === null || !data.file_id) return '';
				return `location.href="${common.CURRENT_URI}/downloader/${data.file_id}"`;
			case 'bs' :
				break;
			default :
				if(window[column[key].kind] === undefined) return '';

				let params = 'null';
				if(column[key].params) {
					if(typeof column[key].params === 'object') {
						if(
							(Array.isArray(column[key].params) && column[key].params.length)
							||
							(!Array.isArray(column[key].params) && Object.keys(column[key].params).length)
						){
							params = JSON.stringify(column[key].params);
						}
					}else{
						params = typeof column[key].params != "string"?column[key].params:`'${column[key].params}'`;
					}
				}
				return `${column[key].kind}(this, ${params})`;
		}
	}
	return onClick;
}

function getListButtons() {
	const data = getListExports();

	if(common.LIST_BUTTONS.hasOwnProperty('excel') && common.LIST_BUTTONS['excel']) {
		data.push(
			{
				text: '<i class="ri-add-line ri-16px me-0 me-sm-2 align-baseline"></i><span class="d-none d-sm-inline-block">' + getLocale('Upload Excel', common.LOCALE) + '</span>',
				className: 'add-new btn btn-primary waves-effect waves-light me-4',
				action: function () {
					if (common.PAGE_EXCEL_URI.length) {
						location.href = common.PAGE_EXCEL_URI;
					}else{
						console.warn('PAGE_EXCEL_URI is not defined')
					}
				}
			}
		);
	}

	if(common.LIST_BUTTONS.hasOwnProperty('add') && common.LIST_BUTTONS['add']) {
		data.push(
			{
				text: '<i class="ri-add-line ri-16px me-0 me-sm-2 align-baseline"></i><span class="d-none d-sm-inline-block">'+getLocale('Add New Record', common.LOCALE)+'</span>',
				className: 'add-new btn btn-primary waves-effect waves-light',
				action: function () {
					if(!common.SIDE_FORM && common.PAGE_ADD_URI.length) {
						location.href = common.PAGE_ADD_URI;
					}else{
						readyFrmInputs(formRecord, 'add', common.FORM_DATA);
					}
				}
			}
		);
	}

	return data;
}

function getListExports() {
	const btns = [];
	for(const kind of common.LIST_EXPORTS) {
		switch (kind) {
			case 'print' :
				btns.push({
					extend: 'print',
					text: `<i class="ri-printer-line me-1" ></i>${getLocale('Print', common.LOCALE)}`,
					className: 'dropdown-item',
					exportOptions: {
						columns: [3, 4, 5, 6, 7],
						// prevent avatar to be display
						format: {
							body: function (inner, coldex, rowdex) {
								if (inner.length <= 0) return inner;
								var el = $.parseHTML(inner);
								var result = '';
								$.each(el, function (index, item) {
									if (item.classList !== undefined && item.classList.contains('user-name')) {
										result = result + item.lastChild.firstChild.textContent;
									} else if (item.innerText === undefined) {
										result = result + item.textContent;
									} else result = result + item.innerText;
								});
								return result;
							}
						}
					},
					customize: function (win) {
						//customize print view for dark
						$(win.document.body)
							.css('color', config.colors.headingColor)
							.css('border-color', config.colors.borderColor)
							.css('background-color', config.colors.bodyBg);
						$(win.document.body)
							.find('table')
							.addClass('compact')
							.css('color', 'inherit')
							.css('border-color', 'inherit')
							.css('background-color', 'inherit');
					}
				});
				break;
			case 'csv' :
				btns.push({
					extend: 'csv',
					text: `<i class="ri-file-text-line me-1" ></i>${getLocale('Csv', common.LOCALE)}`,
					className: 'dropdown-item',
					exportOptions: {
						columns: [1, 2, 3, 4, 5],
						// prevent avatar to be display
						format: {
							body: function (inner, coldex, rowdex) {
								if (inner.length <= 0) return inner;
								var el = $.parseHTML(inner);
								var result = '';
								$.each(el, function (index, item) {
									if (item.classList !== undefined && item.classList.contains('user-name')) {
										result = result + item.lastChild.firstChild.textContent;
									} else if (item.innerText === undefined) {
										result = result + item.textContent;
									} else result = result + item.innerText;
								});
								return result;
							}
						}
					}
				})
				break;
			case 'excel' :
				btns.push({
					extend: 'excel',
					text: `<i class="ri-file-excel-line me-1"></i>${getLocale('Excel', common.LOCALE)}`,
					className: 'dropdown-item',
					exportOptions: {
						columns: [3, 4, 5, 6, 7],
						// prevent avatar to be display
						format: {
							body: function (inner, coldex, rowdex) {
								if (inner.length <= 0) return inner;
								var el = $.parseHTML(inner);
								var result = '';
								$.each(el, function (index, item) {
									if (item.classList !== undefined && item.classList.contains('user-name')) {
										result = result + item.lastChild.firstChild.textContent;
									} else if (item.innerText === undefined) {
										result = result + item.textContent;
									} else result = result + item.innerText;
								});
								return result;
							}
						}
					}
				});
				break;
			case 'pdf' :
				btns.push({
					extend: 'pdf',
					text: `<i class="ri-file-pdf-line me-1"></i>${getLocale('Pdf', common.LOCALE)}`,
					className: 'dropdown-item',
					exportOptions: {
						columns: [1, 2, 3, 4, 5],
						// prevent avatar to be display
						format: {
							body: function (inner, coldex, rowdex) {
								if (inner.length <= 0) return inner;
								var el = $.parseHTML(inner);
								var result = '';
								$.each(el, function (index, item) {
									if (item.classList !== undefined && item.classList.contains('user-name')) {
										result = result + item.lastChild.firstChild.textContent;
									} else if (item.innerText === undefined) {
										result = result + item.textContent;
									} else result = result + item.innerText;
								});
								return result;
							}
						}
					}
				});
				break;
			case 'copy' :
				btns.push({
					extend: 'copy',
					text: `<i class="ri-file-copy-line me-1"></i>${getLocale('Copy', common.LOCALE)}`,
					className: 'dropdown-item',
					exportOptions: {
						columns: [1, 2, 3, 4, 5],
						// prevent avatar to be display
						format: {
							body: function (inner, coldex, rowdex) {
								if (inner.length <= 0) return inner;
								var el = $.parseHTML(inner);
								var result = '';
								$.each(el, function (index, item) {
									if (item.classList !== undefined && item.classList.contains('user-name')) {
										result = result + item.lastChild.firstChild.textContent;
									} else if (item.innerText === undefined) {
										result = result + item.textContent;
									} else result = result + item.innerText;
								});
								return result;
							}
						}
					}
				});
				break;
		}
	}

	if(btns.length > 0) {
		return [
			{
				extend: 'collection',
				className: 'btn btn-outline-secondary dropdown-toggle me-4 waves-effect waves-light',
				text: `<i class="ri-external-link-line me-sm-1"></i> <span class="d-none d-sm-inline-block">${getLocale('Export', common.LOCALE)}</span>`,
				buttons: btns,
			}
		];
	}else{
		return [];
	}
}

function getListActions(btns, dataId) {
	let btnIcons = {
		edit : '<i class="ri-edit-box-line"></i>',
		view : '<i class="ri-eye-line ri-20px"></i>',
		delete : '<i class="ri-delete-bin-7-line ri-20px"></i>',
	};

	let btnHtml = '<div class="d-flex align-items-center gap-50">';
	for(const btn of btns) {
		if(btnIcons[btn] === undefined) {
			console.warn(`${btn} Icon is not defined`);
			continue;
		}
		const title = pascalize(btn);
		btnHtml += `<a href="javascript:;" class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect ${btn}-record" data-bs-toggle="tooltip" title="${title} Record" data-id="${dataId}">${btnIcons[btn]}</a>`;
	}
	btnHtml += '</div>';

	return btnHtml;
}

function afterSelectColumnChange(id, field, value) {
	$.ajax({
		url: common.API_URI+'/'+id,
		method: 'patch',
		data: { [field]: value },
		success: function (json) {
			showAlert({
				type: 'success',
				title: 'Complete',
				text: 'Your Data Is Updated',
				callback: $('.datatables-records').DataTable().ajax.reload(),
				params: [null, false]
			});
		},
		error: function (err) {
			showAlert({
				type: 'warning',
				text: jqXHR.responseJSON.msg,
			});
		}
	});
}

function openViewModal(dataId) {
	const data = getData(dataId);
	console.log(data)
}
