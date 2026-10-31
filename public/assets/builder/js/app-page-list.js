'use strict';

$.fn.dataTable.ext.errMode = 'throw';
let fv, offCanvasEl, isFirstDraw = true;

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
	if(!Object.hasOwn(common, 'LIST_COLUMNS') || !common.LIST_COLUMNS.length) throw new Error(`check common LIST_COLUMNS.`);
	if(dt_table.find('thead th').length !== 1+common.LIST_CHEKBOX+common.LIST_COLUMNS.length)
		throw new Error(`th and LIST_COLUMNS length are not matched.`);

	var dt = dt_table
        .on('preXhr.dt', function (e, settings, json, xhr) {
            $('#loading').show();
        })
        .on('xhr.dt', function (e, settings, json, xhr) {
            if(isFirstDraw) isFirstDraw = false;
            $('#loading').hide();
        })
        .DataTable({
            scrollX: true,
            scrollCollapse: true,
            processing: true,
            serverSide: true,
            paging: common.LIST_PAGING,
            ajax: getAjaxOptions({
                url: common.API_URI,
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
                        filters : {},
                    };

                    // 서버 정렬: 사용자가 고른 컬럼만 보낸다 (서버가 목록 설정·실제 컬럼인지 다시 확인)
                    const order = (data.order || [])[0];
                    if(order) {
                        const column = common.LIST_COLUMNS[order.column - 1 - common.LIST_CHEKBOX];
                        if(column && column.field) {
                            req.orderField = column.field;
                            req.orderDir = order.dir;
                        }
                    }

                    if($('#formFilter').length) req.filters = getFilterData('#formFilter', isFirstDraw);

                    return req;
                },
                complete: function(jqXHR) {
                    const code = jqXHR.responseJSON.code;
                    if(Math.floor(code/10) !== 200) {
                        showAlert({
                            type: 'warning',
                            text: jqXHR.responseJSON.msg,
                        })
                    }
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
                    switch (column.type) {
                        case 'row_num' : // Row Num
                            return {
                                targets: 1+common.LIST_CHEKBOX,
                                searchable: false,
                                orderable: false,
                                render: function (data, type, full, meta) {
                                    // 전체 행 개수
                                    var totalRows = meta.settings._iRecordsDisplay;
                                    // 내림차순 번호 계산
                                    return totalRows - (meta.row + meta.settings._iDisplayStart);
                                    // return meta.row + meta.settings._iDisplayStart + 1;
                                }
                            };
                        case 'actions' : // Actions
                            return {
                                targets: 1+common.LIST_CHEKBOX+common.LIST_COLUMNS.length-1,
                                searchable: false,
                                orderable: false,
                                render: function (data, type, full, meta) {
                                    return getListActions(common.LIST_ACTIONS, full, common.IDENTIFIER);
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
                                // 기본은 정렬 허용, 컬럼 설정 orderable: false 로 끔
                                orderable: column.orderable !== false,
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
                                        // if(column.type === 'button') {
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
            // 처음에는 정렬을 보내지 않음 (모델 기본 정렬) — 사용자가 머리글을 누르면 서버 정렬
            order: [],
            dom:
                '<"card-header d-flex rounded-0 flex-wrap pb-md-0 pt-0 justify-content-end"' +
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
            preInitCallback: function (settings) {
                console.log('preInit')
            },
            preDrawCallback: function(settings) {
                // Datatable 첫 생성 시 및 ajax 실행 이후 각 1회씩 호출됨.
                // console.log('preDrawCallback', settings)
                if(appPlugins.list.datatable.preDrawCallback !== null && typeof appPlugins.list.datatable.preDrawCallback === 'function'){
                    appPlugins.list.datatable.preDrawCallback(settings)
                }
                activateFilterForm('#formFilter', reloadDatatable, this.api());
            },
            initComplete: function (settings, json) {
                // ajax 옵션을 사용해서 테이블이 완전히 초기화되고 데이터가 로드되고 그려지는 시점
                // console.log('initComplete', settings);
                // $('div.loading').remove();
                if(appPlugins.list.datatable.initComplete !== null && typeof appPlugins.list.datatable.initComplete === 'function'){
                    appPlugins.list.datatable.initComplete(settings, json)
                }
            },
            drawCallback: function(settings) {
                // 테이블의 draw 이벤트가 발생할 때마다 취해야 하는 action 을 실행
                // console.log('drawCallback', settings)
                if(appPlugins.list.datatable.drawCallback !== null && typeof appPlugins.list.datatable.drawCallback === 'function'){
                    appPlugins.list.datatable.drawCallback(settings)
                }

                if($(this).find('.datatable-selectpicker').length > 0){
                    $(this).find('.datatable-selectpicker').selectpicker({
                        width: 'fit',
                        container: 'body',
                        style: 'btn-outline-primary p-2',
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

	// 셀 클릭·체크박스·select 변경 (data-* + 위임 이벤트)
	bindListCellEvents();

	// 행 버튼은 document 에 위임한다: 반응형 상세 모달은 .dataTables_wrapper 밖에 그려진다
	// 모달 안의 버튼을 누르면 모달을 먼저 닫는다 (사이드 폼을 가리거나 지운 행이 남지 않도록). 모달 상세(.view-modal)도 같다
	$(document).on('click', '.dtr-bs-modal .view-record, .dtr-bs-modal .edit-record, .dtr-bs-modal .delete-record, .dtr-bs-modal .duplicate-record, .view-modal .edit-record, .view-modal .delete-record', function() {
		const modalEl = this.closest('.modal');
		if(modalEl && window.bootstrap && bootstrap.Modal.getInstance(modalEl)) bootstrap.Modal.getInstance(modalEl).hide();
		else if(modalEl) $(modalEl).modal('hide');
	});

	$(document).on('click', '.view-record', function() {
		if(!common.IDENTIFIER.length) throw new Error(`Identifier is not defined`);
		if(common.PAGE_VIEW_URI) {
			location.href = getRedirectActionUrl(this, common.PAGE_VIEW_URI, common.IDENTIFIER);
		}else{
			openViewModal(getRedirectActionData(this, common.IDENTIFIER));
		}
	});

	$(document).on('click', '.delete-record', function () {
		if(!common.IDENTIFIER.length) throw new Error(`Identifier is not defined`);
		deleteData(getRedirectActionData(this, common.IDENTIFIER), {
			callback: dt.ajax.reload,
			params: [null, false]
		});
	});

	$(document).on('click', '.duplicate-record', function(e) {
		if(!common.IDENTIFIER.length) throw new Error(`Identifier is not defined`);
		executeAjax({
			url: getUrlWithIdentifiers(common.API_URI + '/' + 'duplicate', getRedirectActionData(this, common.IDENTIFIER)),
			method: 'post',
			success: function(response) {
				showAlert({
					type: 'success',
					title: 'Complete',
					text: response.msg,
					callback: dt.ajax.reload,
					params: [null, false]
				});
			},
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

	if(common.FORM_EXIST) {
		const formSelector = '#formRecord';
		const offCanvasElement = document.querySelector('#offcanvasRecord');
		if(offCanvasElement === null) throw new Error(`offCanvasElement is not exist`);

		const formRecord = document.querySelector(formSelector);
		if(formRecord === null) throw new Error(`formRecord is not exist`);

        onLoadedLayout(formRecord);
		preparePlugins(formRecord);
        resetFrmInputs(formRecord, common.FORM_DATA);

		offCanvasEl = new bootstrap.Offcanvas(offCanvasElement);

		offCanvasElement.addEventListener('show.bs.offcanvas', function(e) {
			console.log('offcanvas show');
			refreshPlugins(formRecord, record);
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
		});

		$(document).on('click', '.edit-record', function(e) {
			if(!common.IDENTIFIER.length) throw new Error(`Identifier is not defined`);
			readyFrmInputs(formRecord, 'edit', common.FORM_DATA);
			fetchFrmValues(document.querySelector(formSelector), getRedirectActionData(this, common.IDENTIFIER));
		});

		formRecord.addEventListener('readyFrmInputs', (e) => {
			if(formRecord._mode.value !== 'edit') offCanvasEl.show();
		});

		formRecord.addEventListener("fetchFrmValues", (e) => {
			applyFrmValues(formRecord, record, common.FORM_DATA);
			offCanvasEl.show();
		});

		formRecord.addEventListener("transFrmValues", (e) => {
			offCanvasEl.hide();
		});

		for(const rule of Object.keys(customValidatorsPreset.validators))
			FormValidation.validators[rule] = customValidatorsPreset.validators[rule];

		const dropzoneFieldSet = new Set(
			common.FORM_DATA
				.filter((item) => item.subtype.indexOf('dropzone') !== -1)
				.map((item) => item.field)
		);

		// Form validation for Add new record
		fv = FormValidation.formValidation(
			formRecord,
			{
				fields: reformatFormData(formRecord, common.FORM_DATA, common.FORM_REGEXP, false),
				plugins: {
					// message: new FormValidation.plugins.Message({
					// 	container: function (field, element) {
					// 		// Dropzone 필드 메시지를 특정 컨테이너에 표시
					// 		if (dropzoneList.find((item) => item.field === field)) {
					// 			return document.querySelector(`#${field}-dropzone-wrapper`);
					// 		}
					// 		return element.closest('.form-validation-unit');
					// 	},
					// }),
					trigger: new FormValidation.plugins.Trigger(),
					bootstrap5: new FormValidation.plugins.Bootstrap5({
						// Use this for enabling/changing valid/invalid class
						// eleInvalidClass: '',
						eleValidClass: '',
						rowSelector: function(field, ele) {
							if (dropzoneFieldSet.has(field)) {
								return `#${field}-dropzone-wrapper`;
							}else{
								switch (field) {
									default:
										return '.form-validation-unit';
								}
							}
						},
					}),
					submitButton: new FormValidation.plugins.SubmitButton(),
					// submit button의 type을 submit으로 원할 경우
					// defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
					autoFocus: new FormValidation.plugins.AutoFocus(),
				},
				// init: instance => {
				// 	instance.on('plugins.message.placed', function (e) {
				// 		// console.log('[message placed]', e.field, e.messageElement);
				// 		const containers = e.element.closest('.form-validation-unit').querySelectorAll('.fv-plugins-message-container');
				// 		for(let i = 0; i < containers.length - 1; i++) containers[i].remove();
				// 		//* Move the error message out of the `input-group` element
				// 		if (e.element.parentElement.classList.contains('input-group')) {
				// 			// `e.field`: The field name
				// 			// `e.messageElement`: The message element
				// 			// `e.element`: The field element
				// 			e.element.parentElement.insertAdjacentElement('afterend', e.messageElement);
				// 		}
				// 	});
				//
				// 	instance.on('plugins.essage.displayed', function (e) {
				// 		// console.log('[message displayed]', e.field, e.messageElement);
				// 	});
				// }
			}
		).on('plugins.message.displayed', function (event) {
			// e.messageElement presents the error message element
		}).on('core.field.init', function(event) {
			// When a field is initialized, bind the input event to it
			var field = event.field;
			var element = event.elements[0];  // The field element
			element.addEventListener('change', function() {
				// Revalidate field when flatpickr
				if(element.classList.contains('form-input_date-flatpickr')) fv.revalidateField(field);
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
			console.log('value : ', event.element.type === 'password' ? '********' : event.element.value);
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
						jqXHR.responseJSON.errors.forEach((error) => {
							const field = getFvFieldKey(fv, error.param);
							if (!field) return;
							// 서버 메시지 i18n(선택)
							const msg = getLocale ? getLocale(error.msg, common.LOCALE) : error.msg;
							// 서버 type → FV validator 매핑 (있으면 사용)
							const map = { password_matches: 'identical', required: 'notEmpty' /* ... */ };
							const vKey = map[error.type];
							const hasValidator = vKey && fv.fields[field].validators && fv.fields[field].validators[vKey];
							if (hasValidator) {
								fv.updateValidatorOption(field, vKey, 'message', msg);
								fv.updateFieldStatus(field, 'Invalid', vKey);
							} else {
								// 항상 존재하는 server validator로 폴백
								fv.updateValidatorOption(field, 'server', 'message', msg);
								fv.enableValidator(field, 'server');
								fv.updateFieldStatus(field, 'Invalid', 'server');
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
		$(document).on('click', '.edit-record', function() {
			if(!common.IDENTIFIER.length) throw new Error(`Identifier is not defined`);
			if(!common.PAGE_EDIT_URI.length) throw new Error(`PAGE_EDIT_URI is not defined`);
			location.href = getRedirectActionUrl(this, common.PAGE_EDIT_URI, common.IDENTIFIER);
		});
	}
});

function renderColumn(data, type, full, meta, column) {
	let wrap;
	switch (column.type) {
		case 'button':
			wrap = document.createElement('button');
			wrap.classList.add('btn', 'btn-sm', 'btn-info', 'waves-effect', 'waves-light', 'pe-3', 'ps-3');
			break;
        case 'checkbox':
            wrap = document.createElement('label');
            wrap.classList.add('d-inline-block')
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
		for(const className of column.classes) wrap.classList.add(className);

	// inner: 서버 값은 텍스트 노드로만 넣는다 (HTML 로 해석되지 않도록)
	let inner;
	if(['recent_dt', 'created_dt', 'updated_dt'].includes(column.field)) {
		if(!data) return '-';
		const dateObj = new Date(data);
		inner = document.createTextNode(dateObj.getFullYear().toString() + '-' + (dateObj.getMonth()+1).toString().padStart(2, '0') + '-' + dateObj.getDate().toString().padStart(2, '0'));
	}else{
		if(Object.hasOwn(column.onclick, 'kind')
			&& column.onclick.kind === 'download'
			&& !column.text
		) {
			column.text = 'Download';
		}

		switch (column.type) {
			case 'text':
				inner = document.createTextNode(column.text?getLocale(column.text, common.LOCALE):getListOptionLabel(column, data));
				break;
			case 'button':
                if(data === undefined) {
                    console.warn(`${column.field} is not defined.`, column)
                    return '-';
                }else if(['popup', 'redirect', 'download'].includes(column.onclick.kind)
                    && (data === null || data.length === 0)) {
                    console.warn(`${column.field} onclick data is empty`, column)
                    return '-';
                }
				inner = document.createTextNode(column.text?getLocale(column.text, common.LOCALE):getLocale(column.field, common.LOCALE));
				break;
			case 'icon':
				inner = document.createElement('i');
				inner.className = column.icon ?? '';
				break;
			case 'img':
				if(data === null || !data.length || !Object.hasOwn(data[0], 'file_link')) return '-';
				inner = createListImage(data[0].file_link);
				wrap.setAttribute('data-bs-content', inner.outerHTML);
				break;
			case 'select':
				inner = document.createElement('select');
				break;
			case 'checkbox':
				switch (column.subtype) {
					case 'boolean' :
						inner = createListCheckbox(full, column, Number(data) === 1);
						break;
					default :
						inner = document.createTextNode((data === null || data === undefined || data === '') ? '-' : data);
				}
				break;
			default :
				inner = document.createTextNode('-');
		}
	}

	// return
	if(isArray(full[column.field])) {
		const html = full[column.field].reduce((acc, curr) => {
			let currInner = inner;
			if(column.type === 'img') {
				// 파일 배열이면 원소마다 이미지를 만든다
				if(curr === null || typeof curr !== 'object' || !Object.hasOwn(curr, 'file_link')) return acc;
				currInner = createListImage(curr.file_link);
				wrap.setAttribute('data-bs-content', currInner.outerHTML);
			}
			return acc += renderColumnHTML(curr, full, column, wrap, currInner);
		}, '');
		return `<div>${html}</div>`;
	}else{
		return renderColumnHTML(data, full, column, wrap, inner);
	}
}

/**
 * 선택형 컬럼(option_attributes → options)이면 값 대신 라벨. 다중 값은 seperator(기본 ,)로 나눠 바꾼다
 */
function getListOptionLabel(column, data) {
	if(data === null || data === undefined) return '';
	const options = column.options;
	if(!options || typeof options !== 'object' || isArray(options) || !Object.keys(options).length) return data;
	const seperator = column.option_attributes?.seperator ?? ',';
	return String(data).split(seperator).map((value) => options[value.trim()] ?? value).join(`${seperator} `);
}

function createListImage(src) {
	const img = document.createElement('img');
	img.className = 'img-thumbnail d-inline rounded-2 overflow-hidden';
	if(isSafeListUrl(src)) img.setAttribute('src', src);
	return img;
}

/**
 * 목록 셀 체크박스. 변경은 data-* 와 위임 이벤트(bindListCellEvents)로 처리한다 (inline onchange 금지)
 *   data-identifiers : 식별자 JSON, data-field : 필드명, data-onchange : 호출할 함수명(기본 afterCheckboxColumnChange)
 */
function createListCheckbox(full, column, checked, disabled = false, funcName = 'afterCheckboxColumnChange') {
	const input = document.createElement('input');
	input.type = 'checkbox';
	input.value = '1';
	input.className = 'form-check-input list-cell-checkbox';
	if(checked) input.setAttribute('checked', 'checked');
	if(disabled) input.setAttribute('disabled', 'disabled');
	input.setAttribute('data-identifiers', getIdentifiersData(full, common.IDENTIFIER, true));
	input.setAttribute('data-field', column.field);
	input.setAttribute('data-onchange', funcName);
	return input;
}

/**
 * inner 는 DOM 노드(빌더가 만드는 셀) 또는 HTML 문자열(화면별 커스텀 렌더러 호환용).
 * 서버 값을 문자열로 넘기지 말고 노드로 만들어 넘길 것.
 */
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
				identifiers : getIdentifiersData(full, common.IDENTIFIER, true),
				value : value,
			}).map(([key, value]) => [`data-${key}`, value])
		)
	};

	if(Object.keys(column.onclick).length) {
		wrap.classList.add('cursor-pointer');
		const action = getColumnClickAction(data, full, column);
		if(action) attrs['data-cell-click'] = JSON.stringify(action);

		if(!Object.hasOwn(column.onclick, 'noValue')) column.onclick.noValue = false;
		if(!column.onclick.noValue && value === '') inner = '';

        switch (column.onclick.kind) {
            case 'bs' :
                if(column.onclick.noValue || value !== '') {
                    if(!Object.hasOwn(column.onclick, 'attrs')) column.onclick.attrs = {};
                    if(Object.keys(column.onclick.attrs).length) {
                        Object.entries(column.onclick.attrs).map(([key, value]) => attrs[`data-bs-${key}`] = value )
                    }
                }
                break;
            case 'redirect' :
                break;
            case 'view' :
                if(column.type !== 'button') {
                    wrap.classList.add('text-primary', 'text-decoration-underline')
                }
                break;
        }
	}

	Object.entries(attrs).map(([key, value]) => wrap.setAttribute(key, value));

	// return
	if(inner instanceof Node) {
		wrap.replaceChildren(inner.cloneNode(true));
	}else{
		wrap.innerHTML = inner ?? '';
	}
	return wrap.outerHTML;
}

function renderSelectColumn(data, type, full, meta, column) {
	const wrap = document.createElement('div');
	wrap.classList.add('bootstrap-select', 'w-px-100')
	const select = document.createElement('select');
	select.classList.add('datatable-selectpicker','w-px-100')

	if(Object.hasOwn(column, 'options')) {
		for(const value of Object.keys(column.options)){
			const option = document.createElement('option');
			option.value = value;
			option.innerText = column.options[value];
			if(data !== null && data !== undefined && String(data) === value) option.setAttribute('selected', 'selected')
			select.appendChild(option);
		}
	}

	let funcName = 'afterSelectColumnChange';
	if(Object.hasOwn(column, 'onChange') || Object.hasOwn(column, 'onchange')) {
		const key = Object.hasOwn(column, 'onChange')?'onChange':'onchange';
		funcName = column[key];
	}
	// 변경은 위임 이벤트(bindListCellEvents)로 처리 (inline onchange 금지)
	select.setAttribute('data-identifiers', getIdentifiersData(full, common.IDENTIFIER, true));
	select.setAttribute('data-field', column.field);
	select.setAttribute('data-onchange', funcName);

	wrap.appendChild(select)
	return wrap.outerHTML;
}

function renderButtonColumn(data, type, full, meta, column) {
	const wrap = document.createElement('button');
	wrap.classList.add('btn', 'btn-info', 'waves-effect', 'waves-light', 'pe-3', 'ps-3');

	// class
	if(column.classes.length)
		for(const className of column.classes) wrap.classList.add(className);

	if(Object.hasOwn(column.onclick, 'kind')
		&& column.onclick.kind === 'download'
		&& !column.text
	) {
		column.text = 'Download';
	}

	const inner = document.createTextNode(column.text?getLocale(column.text, common.LOCALE):getLocale(column.field, common.LOCALE));

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

/**
 * javascript:·data:·vbscript: 같은 스킴은 막는다 (상대 경로·http(s)·mailto·tel 허용)
 */
function isSafeListUrl(url) {
	if(url === null || url === undefined) return false;
	const value = String(url).trim().replace(/[\u0000-\u001F\u007F]/g, '');
	return !/^(javascript|data|vbscript):/i.test(value);
}

/**
 * 셀 클릭 동작을 data-cell-click(JSON)로 표현한다. bindListCellEvents 가 실행한다.
 *   {type:'url', url, target} | {type:'modal', ids, target} | {type:'popup', url, title} | {type:'func', func, params}
 */
function getColumnClickAction(data, full, column) {
	const key = Object.hasOwn(column, 'onClick')?'onClick':'onclick';
	if(!Object.hasOwn(column, key)) return null;
	const onclick = column[key];
	if(!onclick.kind) throw new Error(`getColumnClickAction : onclick kind is not defined. (${column.field})`);

	const ids = () => getIdentifiersData(full, onclick.params??common.IDENTIFIER);
	switch (onclick.kind) {
		case 'view' : {
			let uri;
			if(onclick.attrs && Object.hasOwn(onclick.attrs, 'href')) uri = onclick.attrs.href;
			else if(common.PAGE_VIEW_URI) uri = common.PAGE_VIEW_URI;

			const target = onclick.attrs?.target ?? '_self';
			if(uri && ['_self', '_blank'].includes(target)) {
				return {type: 'url', url: getUrlWithIdentifiers(uri, ids()), target: target};
			}
			return {type: 'modal', ids: ids(), target: ['_self', '_blank'].includes(target) ? '' : target};
		}
		case 'redirect' :
			return {type: 'url', url: data, target: onclick.attrs?.target === '_blank' ? '_blank' : '_self'};
		case 'download' :
			if(data === null || data === undefined || !data.file_id) return null;
			return {type: 'url', url: `${common.CURRENT_URI}/downloader/${encodeURIComponent(data.file_id)}`, target: '_self'};
		case 'popup' : {
			// 셀 값(링크) 또는 attrs.href + 행 식별자를 화면 전체 모달의 iframe 으로 연다
			const url = onclick.attrs?.href ? getUrlWithIdentifiers(onclick.attrs.href, ids()) : data;
			if(url === null || url === undefined || String(url).trim() === '') return null;
			const title = onclick.attrs?.title ?? column.label ?? column.field;
			return {type: 'popup', url: String(url).trim(), title: getLocale(title, common.LOCALE)};
		}
		case 'bs' :
			return null;
		default : {
			if(typeof window[onclick.kind] !== 'function') return null;
			let params = null;
			if(onclick.params !== undefined && onclick.params !== null && onclick.params !== '') {
				if(typeof onclick.params !== 'object' || Object.keys(onclick.params).length) params = onclick.params;
			}
			return {type: 'func', func: onclick.kind, params: params};
		}
	}
}

// 이전 이름 호환: 지금은 inline onclick 문자열을 만들지 않는다
function getColumnOnclick(data, full, column) {
	return '';
}

/**
 * 목록 셀의 클릭·변경을 위임 이벤트로 처리한다. 반응형 상세 모달(본문 밖)도 포함하려고 document 에 건다.
 */
function bindListCellEvents() {
	if(window.__listCellEventsBound) return;
	window.__listCellEventsBound = true;

	$(document).on('click', '[data-cell-click]', function(e) {
		let action;
		try { action = JSON.parse(this.getAttribute('data-cell-click')); } catch (error) { return; }
		if(!action) return;

		switch (action.type) {
			case 'url' :
				if(!isSafeListUrl(action.url)) return;
				if(action.target === '_blank') window.open(action.url, '_blank', 'noopener');
				else location.href = action.url;
				break;
			case 'modal' :
				openViewModal(action.ids, action.target);
				break;
			case 'popup' :
				openPopupModal(action.url, action.title);
				break;
			case 'func' :
				if(typeof window[action.func] === 'function') window[action.func](this, action.params);
				break;
		}
	});

	$(document).on('change', 'input.list-cell-checkbox[data-onchange], select.datatable-selectpicker[data-onchange]', function(e) {
		if(this.disabled) return;
		const func = this.getAttribute('data-onchange');
		if(typeof window[func] !== 'function') return;
		let ids = {};
		try { ids = JSON.parse(this.getAttribute('data-identifiers') || '{}'); } catch (error) { return; }
		const value = this.type === 'checkbox' ? (this.checked ? 1 : 0) : this.value;
		window[func](ids, this.getAttribute('data-field'), value);
	});
}

function getListButtons() {
	const data = getListExports();

	for(const btnName of Object.keys(common.LIST_BUTTONS)) {
		if(['excel', 'add'].includes(btnName)) continue;

		const btnObj = common.LIST_BUTTONS[btnName];
		// 끈 버튼(false)·잘못 적은 값은 건너뜀 (그대로 그리면 빈 버튼이 생겼음)
		if(btnObj === null || typeof btnObj !== 'object') continue;
		data.push(
			{
				text: '<span class="d-none d-sm-inline-block">' + getLocale(btnObj.text, common.LOCALE) + '</span>',
				className: (Array.isArray(btnObj.classes) ? btnObj.classes.join(' ') : btnObj.classes) || 'btn btn-primary waves-effect waves-light me-4',
				action: function () {
					if(btnObj.action !== undefined && btnObj.action.length) {
						window[btnObj.action](this, btnObj.params??null);
					}else{
						console.warn(`${btnName} Action is not Defined`);
					}
				}
			}
		);
	}

	// 체크박스로 고른 행 삭제 (체크박스가 켜져 있고 행 삭제 권한이 있을 때)
	if(common.LIST_CHEKBOX && Array.isArray(common.LIST_ACTIONS) && common.LIST_ACTIONS.includes('delete')) {
		data.push(
			{
				text: '<i class="ri-delete-bin-7-line ri-16px me-0 me-sm-2 align-baseline"></i><span class="d-none d-sm-inline-block">' + getLocale('Delete Selected', common.LOCALE) + '</span>',
				className: 'btn btn-outline-danger waves-effect me-4',
				action: function () {
					deleteSelectedRecords();
				}
			}
		);
	}

	if(Object.hasOwn(common.LIST_BUTTONS, 'excel') && common.LIST_BUTTONS['excel']) {
		data.push(
			{
				text: '<i class="ri-add-line ri-16px me-0 me-sm-2 align-baseline"></i><span class="d-none d-sm-inline-block">' + getLocale('Upload Excel', common.LOCALE) + '</span>',
				className: 'btn btn-primary waves-effect waves-light me-4',
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

	if(Object.hasOwn(common.LIST_BUTTONS, 'add') && common.LIST_BUTTONS['add']) {
		data.push(
			{
				text: '<i class="ri-add-line ri-16px me-0 me-sm-2 align-baseline"></i><span class="d-none d-sm-inline-block">'+getLocale('Add New Record', common.LOCALE)+'</span>',
				className: 'btn btn-primary waves-effect waves-light',
				action: function () {
					if(common.FORM_EXIST) {
						readyFrmInputs(document.querySelector('#formRecord'), 'add', common.FORM_DATA);
					}else if(common.PAGE_ADD_URI && common.PAGE_ADD_URI.length) {
						location.href = common.PAGE_ADD_URI;
					}else{
						console.warn('PAGE_ADD_URI is not defined');
					}
				}
			}
		);
	}

	return data;
}

/**
 * 체크한 행을 하나씩 DELETE (행마다 서버의 권한·데이터 범위·삭제 hook 을 그대로 거친다)
 */
function deleteSelectedRecords() {
	const table = $('.datatables-records').DataTable();
	const targets = [];
	$('.datatables-records tbody input.dt-checkboxes:checked').each(function() {
		const full = table.row($(this).closest('tr')).data();
		if(full) targets.push(getIdentifiersData(full, common.IDENTIFIER));
	});

	if(!targets.length) {
		showAlert({type: 'warning', text: getLocale('No rows selected', common.LOCALE)});
		return;
	}

	Swal.fire({
		title: getLocale('Do you really want to delete?', common.LOCALE),
		text: `${targets.length} / ` + getLocale('You can\'t undo this action', common.LOCALE),
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
		if(!result.isConfirmed) return;

		let done = 0;
		let failed = 0;
		targets.forEach(function(idData) {
			executeAjax({
				async: false,
				url: getUrlWithIdentifiers(common.API_URI, Object.keys(idData).length === 1 ? Object.values(idData)[0] : idData, common.API_PARAMS),
				method: 'delete',
				success: function() { done++; },
				error: function() { failed++; },
			});
		});

		showAlert({
			type: failed ? 'warning' : 'success',
			text: getLocale('Deleted', common.LOCALE) + ` ${done}` + (failed ? ` / ` + getLocale('Failed', common.LOCALE) + ` ${failed}` : ''),
			callback: function() { table.ajax.reload(null, false); },
		});
	});
}

function getListExports() {
	const btns = [];
	// control·checkbox·actions 열을 뺀 LIST_COLUMNS 열 인덱스
	const exportColumns = common.LIST_COLUMNS
		.map((column, index) => column.type === 'actions' ? null : 1 + common.LIST_CHEKBOX + index)
		.filter((index) => index !== null);
	for(const kind in common.LIST_EXPORTS) {
		if(common.LIST_EXPORTS[kind] === false) continue;

		switch (kind) {
			case 'print' :
				btns.push({
					extend: 'print',
					text: `<i class="ri-printer-line me-1" ></i>${getLocale('Print', common.LOCALE)}`,
					className: 'dropdown-item',
					exportOptions: {
						columns: exportColumns,
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
			case 'excel' :
				btns.push({
					extend: kind,
					text: kind === 'csv'
						? `<i class="ri-file-text-line me-1" ></i>${getLocale('Csv', common.LOCALE)}`
						: `<i class="ri-file-excel-line me-1"></i>${getLocale('Excel', common.LOCALE)}`,
					className: 'dropdown-item',
					action: function (e, dt, node, config) {
                        prepareExports(kind, getFilterData('#formFilter'));
					},
				})
				break;
			case 'pdf' :
				btns.push({
					extend: 'pdf',
					text: `<i class="ri-file-pdf-line me-1"></i>${getLocale('Pdf', common.LOCALE)}`,
					className: 'dropdown-item',
					exportOptions: {
						columns: exportColumns,
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
						columns: exportColumns,
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

function getListActions(btns, full, identifiers = []) {
	let btnIcons = {
		edit : '<i class="ri-edit-box-line"></i>',
		view : '<i class="ri-eye-line ri-20px"></i>',
		delete : '<i class="ri-delete-bin-7-line ri-20px"></i>',
		duplicate : '<i class="ri-file-copy-2-fill ri-20px"></i>',
	};

	let btnHtml = '<div class="d-flex align-items-center gap-50">';
	for(const btn of btns) {
		if(btnIcons[btn] === undefined) {
			console.warn(`${btn} Icon is not defined`);
			continue;
		}
		const title = pascalize(btn);

		const a = document.createElement('a');
		a.href = 'javascript:void(0);';
		a.classList.add('btn', 'btn-sm', 'btn-icon', 'btn-text-secondary', 'rounded-pill', 'waves-effect', `${btn}-record`);
		a.setAttribute('data-bs-toggle', 'tooltip');
		a.setAttribute('title', `${title} Record`);
		a.innerHTML = btnIcons[btn];

		if(identifiers.length) {
			const idData = getIdentifiersData(full, identifiers);
			if(Object.keys(idData).length === 1) {
				a.setAttribute('data-id', Object.values(idData)[0]);
			}else{
				for(const field of Object.keys(idData)) a.setAttribute('data-'+field, idData[field]);
			}
		}

		btnHtml += a.outerHTML;
	}
	btnHtml += '</div>';

	return btnHtml;
}

function afterSelectColumnChange(idData, field, value) {
    executeAjax({
		url: getUrlWithIdentifiers(common.API_URI, idData),
		method: 'patch',
		data: { [field]: value },
		success: function (response) {
			showAlert({
				type: 'success',
				title: 'Complete',
				text: 'Your Data Is Updated',
				callback: $('.datatables-records').DataTable().ajax.reload,
				params: [null, false]
			});
		},
		error: function (jqXHR, textStatus, errorThrown) {
			showAlert({
				type: 'warning',
				text: jqXHR.responseJSON.msg,
			});
		}
	});
}

/**
 * 팝업으로 열 수 있는 주소인지: http(s) 또는 같은 사이트 경로만 (iframe 에 mailto·tel 등은 의미가 없다)
 */
function getPopupUrl(url) {
	if(!isSafeListUrl(url)) return null;
	let parsed;
	try { parsed = new URL(url, location.href); } catch (error) { return null; }
	return ['http:', 'https:'].includes(parsed.protocol) ? parsed : null;
}

/**
 * 목록 셀 onclick.kind 'popup': 화면 전체 모달 안 iframe 으로 주소를 연다
 * iframe 을 막는 외부 사이트가 많아 머리글에 새 창으로 열기 링크를 둔다. 닫으면 iframe 을 비운다(영상·소리 정지)
 */
function openPopupModal(url, title = '') {
	const parsed = getPopupUrl(url);
	if(!parsed) {
		console.warn('openPopupModal : unsupported url', url);
		return;
	}

	let modalEl = document.getElementById('listPopupModal');
	if(!modalEl) {
		modalEl = document.createElement('div');
		modalEl.className = 'modal fade';
		modalEl.id = 'listPopupModal';
		modalEl.tabIndex = -1;
		modalEl.setAttribute('aria-hidden', 'true');
		modalEl.innerHTML = `
			<div class="modal-dialog modal-fullscreen">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title text-truncate"></h5>
						<a class="btn btn-sm btn-outline-secondary ms-auto me-2 popup-new-window" target="_blank" rel="noopener noreferrer"></a>
						<button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
					</div>
					<div class="modal-body p-0">
						<iframe class="w-100 h-100 border-0"></iframe>
					</div>
				</div>
			</div>`;
		modalEl.querySelector('.popup-new-window').textContent = getLocale('Open In New Window', common.LOCALE);
		modalEl.querySelector('.btn-close').setAttribute('aria-label', getLocale('Close', common.LOCALE));
		modalEl.addEventListener('hidden.bs.modal', () => {
			modalEl.querySelector('iframe').src = 'about:blank';
		});
		document.body.append(modalEl);
	}

	const iframe = modalEl.querySelector('iframe');
	// 외부 주소에는 관리자 화면 주소(referrer)를 보내지 않는다
	if(parsed.origin === location.origin) iframe.removeAttribute('referrerpolicy');
	else iframe.setAttribute('referrerpolicy', 'no-referrer');
	iframe.title = title;
	iframe.src = parsed.href;

	modalEl.querySelector('.modal-title').textContent = title;
	modalEl.querySelector('.popup-new-window').href = parsed.href;

	bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

/**
 * 목록의 모달 상세 보기 (list method properties.viewConfig → view_modal.php)
 * 수정·삭제 버튼에 행 식별자를 넣어 목록의 .edit-record·.delete-record 처리를 그대로 쓴다
 */
function openViewModal(dataId, modalId = '') {
	const modalEl = document.getElementById(modalId || 'viewModal');
	if(!modalEl || typeof applyViewData !== 'function') {
		console.warn('openViewModal : view modal is not defined (list properties.viewConfig)');
		return;
	}

	if(dataId === null || typeof dataId !== 'object') dataId = {[common.IDENTIFIER[0]]: dataId};

	const data = applyViewData(dataId, modalEl.querySelector('.modal-body'));
	if(!data) {
		showAlert({type: 'warning', text: getLocale('Data Not Exist', common.LOCALE)});
		return;
	}

	const actions = Array.isArray(common.LIST_ACTIONS) ? common.LIST_ACTIONS : [];
	let canEdit = actions.includes('edit') && !!(common.FORM_EXIST || common.PAGE_EDIT_URI);
	let canDelete = actions.includes('delete');
	// 작성자만 수정·삭제할 수 있는 화면(list properties.checkOwner)
	if((canEdit || canDelete) && common.VIEW_CHECK_OWNER && !isMyData(dataId, false)) {
		canEdit = false;
		canDelete = false;
	}

	modalEl.querySelectorAll('.edit-record, .delete-record').forEach((button) => {
		// getRedirectActionData 가 jQuery data 로 읽는다
		Object.keys(dataId).forEach((key) => $(button).data(key, dataId[key]));
		const show = button.classList.contains('edit-record') ? canEdit : canDelete;
		button.classList.toggle('d-none', !show);
	});

	bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function afterCheckboxColumnChange(idData, field, value) {
    executeAjax({
		url: getUrlWithIdentifiers(common.API_URI, idData),
		method: 'patch',
		data: { [field]: value },
		success: function (response) {
			showAlert({
				type: 'success',
				title: 'Complete',
				text: 'Your Data Is Updated',
				callback: $('.datatables-records').DataTable().ajax.reload,
				params: [null, false]
			});
		},
		error: function (jqXHR, textStatus, errorThrown) {
			showAlert({
				type: 'warning',
				text: jqXHR.responseJSON.msg,
			});
		}
	});
}

function reloadDatatable(datatable) {
    datatable.ajax.reload();
}
