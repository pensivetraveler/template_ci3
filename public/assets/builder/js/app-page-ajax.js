function getAjaxOptions(obj = {}) {
	try {
		if(obj.url === undefined) throw new Error(`getAjaxOptions : url is not valid !`);

		let url = obj.url;
		let method = 'get';
		['method', 'type'].forEach(function(key) {
			if(obj[key] !== undefined) {
				if(typeof obj[key] !== 'string') throw new Error(`getAjaxOptions : method is not valid !`);
				if(!isEmpty(obj[key]) && !['get', 'post', 'delete', 'put', 'patch'].includes(obj[key].toLowerCase()))
					throw new Error(`getAjaxOptions : method is not valid !`);
				method = obj[key];
			}
		})

		let dataType = 'json';
		if(obj.dataType !== undefined) {
			if(typeof obj.dataType !== 'string') throw new Error(`getAjaxOptions : dataType is not valid !`);
			dataType = obj.dataType.toLowerCase();
		}

		let data = {};
		if(obj.data !== undefined) {
			if(!isObject(obj.data) && typeof obj.data !== 'function' && typeof obj.data !== 'string')
				throw new Error(`getAjaxOptions : data is not valid type !`);
			data = obj.data;
		}

		const async = obj.async === undefined?false:obj.async;

		const ajaxOption = {
			async: false,
			url: url,
			method: method,
			data: data,
			dataType: dataType,
		};

		for(const key of ['headers', 'complete', 'contentType', 'processData']){
			if(obj.hasOwnProperty(key)) ajaxOption[key] = obj[key];
		}

		return ajaxOption;
	} catch (error) {
		customErrorHandler(error);
	}
}

function executeAjax(obj = {}, test = false) {
	const options = getAjaxOptions(obj);

	if(obj.success !== undefined) {
		options.success = obj.success;
	}else{
		options.success = function(response, textStatus, jqXHR) {
			console.log(response)
			if (Math.floor(response.code/10) === 200) {
				if(obj.after !== undefined && obj.after.callback !== undefined) {
					if(obj.after.callback.name === 'showAlert'){
						if(obj.after.params.text === undefined) obj.after.params.text = response.msg;
						showAlert(obj.after.params);
					}else{
						callUserFunc(obj.after.callback, obj.after.params);
					}
				}else{
					showAlert({
						type: 'success',
						text: response.msg,
					});
				}
			} else {
				console.warn(jqXHR.responseJSON)
				showAlert({
					type: 'warning',
					text: jqXHR.responseJSON.msg,
				});
			}
		}
	}

	if(obj.error !== undefined) {
		options.error = obj.error;
	}else{
		options.error = function(jqXHR, textStatus, errorThrown) {
			console.warn(jqXHR.responseJSON)
			showAlert({
				type: 'error',
				text: jqXHR.responseJSON.msg,
			});
		}
	}

	if(test) {
		const form = document.createElement('form');
		if(obj.data !== undefined) {
			for(const [name, value] of Object.entries(obj.data)) {
				const input = document.createElement('input');
				input.name = name;
				input.value = value;
				form.appendChild(input);
			}
		}
		form.target = '_blank';
		form.action = obj.url;
		form.method = obj.method;
		document.body.appendChild(form);
		form.submit();
		form.remove();
	}else{
		$.ajax(options);
	}
}

function submitAjax(selector, options = {}, test = false) {
	const form = document.querySelector(selector);
	const formData = options.hasOwnProperty('data') ? options.data : getFormData(form);

	let url;
	if(!options.hasOwnProperty('url')) {
		url = common.API_URI;
		if(common.hasOwnProperty('API_URI_ADD')&&common.API_URI_ADD.length>0) url += '/'+common.API_URI_ADD;
		if(form[common.IDENTIFIER]) url += '/' + form[common.IDENTIFIER].value;
		if(common.API_PARAMS.length) url += '?' + new URLSearchParams(common.API_PARAMS).toString();
	}else{
		url = options.url;
	}

	options = Object.assign({
		url : url,
		method: 'post',
		headers: {
			'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
		},
		contentType: false, // jQuery가 contentType을 자동으로 설정하지 않도록 함
		processData: false, // jQuery가 데이터를 처리하지 않도록 함
		data: formData,
		success: function(response) {
			console.log(response)
			showAlert({
				type: 'success',
				title: 'Complete',
				text: form['_mode'].value === 'edit' ? 'Your Data Is Updated' : 'Registered Successfully',
			});
		},
	}, options);

	if(test) {
		form.querySelectorAll('input, textarea, select').forEach(function(node) {
			if(!node.name) return;
			if(!checkInputSubmittable(node, form)) node.setAttribute('disabled', 'disabled');
		});

		form.action = options.url;
		form.method = options.method ?? 'post';
		form.target = '_blank';
		form.submit();

		form.querySelectorAll('[disabled]').forEach(function(node) {
			node.removeAttribute('disabled');
		});
	}else{
		executeAjax(options);
	}
}

function getFormData(form = null) {
	if(!form) form = document.getElementById('formRecord');

	const formData = new FormData();
	form.querySelectorAll('input, textarea, select').forEach(function(node) {
		if(!node.name) return;
		if(checkInputSubmittable(node, form)){
			if(node.type === 'file') {
				let fileCnt = node.files.length;
				if(fileCnt > 0) {
					if(fileCnt === 1) {
						formData.append(node.name, node.files[0]);
					}else{
						if(node.hasAttribute('max') && !isNaN(node.getAttribute('max'))) {
							fileCnt = parseInt(node.hasAttribute('max'));
						}
						for(let i = 0; i < fileCnt; i++) {
							formData.append(node.name+'[]', node.files[i]);
						}
					}
				}
			}else{
				formData.append(node.name, node.value);
			}
		}
	});

	if(window['Dropzone'] !== undefined) {
		for(const inst of Dropzone.instances){
			let field = inst.element.getAttribute('data-field');
			if(inst.files.length > 0) {
				if(inst.files.length === 1) {
					formData.append(`${field}`, inst.files[0]);
				} else {
					inst.files.forEach((file, index) => {
						formData.append(`${field}[${index}]`, file);
					});
				}
			}
		}
	}

	logFormData(formData);

	return formData;
}

function checkInputSubmittable(node, form) {
	if(node.type === 'file') {
		if(node.files.length > 0) return true;
	}else{
		if(node.hasAttribute('data-detect-changed') && !isAttributeValueTrue(node, 'data-detect-changed')) {
			return true;
		}else if(node.type === 'hidden') {
			return true;
		}else if(node.type === 'checkbox') {
			if(node.checked === true) return true;
		}else if(node.getAttribute('required') === 'required') {
			return true;
		}else if(isAttributeValueTrue(node, 'data-input-changed')) {
			return true;
		}else if(node.hasAttribute('required-mod')) {
			const requireMod = node.getAttribute('required-mod').split('|');
			if (requireMod.includes(form['_mode'].value)) return true;
		}
	}
	return false;
}

function checkDuplicate(button) {
	try {
		const fieldName = button.getAttribute('data-rel-field');
		if(!fieldName) throw new Error(`checkDuplicate : fieldName is not defined !`);

		const form = button.closest('form');
		if(!form.hasOwnProperty(fieldName)) throw new Error(`checkDuplicate : fieldName is not valid !`);

		const input = form[fieldName];
		const hidden = form.querySelector(`[name="${input.name}_unique"]`);
		const value = input.value;
		const originalValue = input.getAttribute('data-original-value');

		// 같은 값인 경우 중복 체크 하지 않음.
		if(originalValue && originalValue === value) return;

		// checked val 임시 처리
		if(form['_event'] !== undefined) form['_event'].value = 'dup_check';
		fv.revalidateField(input.name).then((status) => {
			if(status === 'Valid') {
				executeAjax({
					url: common.API_URI + '/checkDuplicate',
					headers: {
						'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
					},
					data: {
						field: fieldName,
						value: input.value,
					},
					success: function(response, textStatus, jqXHR) {
						console.log(response)
						hidden.value = 1;
						button.setAttribute('disabled', 'disabled');
						showAlert({
							type: 'success',
							text: response.msg,
						});
					},
				});
			}else{
				hidden.value = '';
			}
		});
	} catch (error) {
		customErrorHandler(error);
	}
}

function downloadFile(fileId) {
	const url = location.origin+location.pathname;
	location.href = url+'/downloader/'+fileId;
}

function deleteFile(btn, type = '') {
	const form = btn.closest('form');
	const identifier = form[common.IDENTIFIER].value;

	const listWrap = btn.closest('ul');
	const itemWrap = btn.closest('.form-list-item');
	if(!itemWrap.hasAttribute('data-full-item')) return;
	const item = JSON.parse(itemWrap.getAttribute('data-full-item').replace(/'/g, '"'));

	executeAjax({
		url : common.API_URI + '/deleteFile/' + identifier + (type ? '?type='+type : ''),
		headers : {
			'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
		},
		method : 'patch',
		data : item,
		success: function(response) {
			console.log(response)
			showAlert({
				type: 'success',
				title: 'Complete',
				text: response.msg,
				callback: reload,
			});
			itemWrap.remove();
			if(!listWrap.children.length) listWrap.classList.add('d-none');
		},
	});
}

function deleteRepeater(repeater, deleteElement) {
	const data = {};
	$(repeater).find('input, select, textarea').each(function(i , item) {
		if(item.type === 'file') return;
		data[item.getAttribute('data-group-field')] = item.value;
	});

	executeAjax({
		url : common.API_URI + '/deleteRepeater/' + identifier,
		headers : {
			'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
		},
		method : 'patch',
		data : data,
		success: function(response) {
			console.log(response)
			showAlert({
				type: 'success',
				title: 'Complete',
				text: response.msg,
			});

			$(repeater).slideUp(deleteElement)
		},
	});
}

function getData(dataId = null, params = {}) {
	if(!dataId) throw new Error(`dataId is not defined`);

	let dataParams;
	if(common.API_PARAMS.length === 0){
		dataParams = params;
	}else{
		dataParams = { ...common.API_PARAMS, ...params };
	}

	let data = null;
	$.ajax({
		async: false,
		url : common.API_URI + '/' + dataId + '?' + new URLSearchParams(dataParams).toString(),
		headers: {
			'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
		},
		dataType: 'json',
		success: function (response, textStatus, jqXHR) {
			data = response.data[0];
		},
		error: function (jqXHR, textStatus, errorThrown) {
			console.log(jqXHR)
		}
	});
	return data;
}

function deleteData(dataId = null, callback = {}) {
	if(!dataId) throw new Error(`dataId is not defined`);

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
			if(Object.keys(callback).length === 0) {
				if(location.origin+location.pathname === common.PAGE_LIST_URI) {
					callback.callback = reload;
				}else{
					callback.callback = redirect;
					callback.params = common.PAGE_LIST_URI;
				}
			}

			executeAjax({
				url: common.API_URI + '/' + dataId + (Object.keys(common.API_PARAMS).length > 0 ? '?' + new URLSearchParams(common.API_PARAMS).toString() : ''),
				method: 'delete',
				after : {
					callback: showAlert,
					params: {
						type: 'success',
						title: 'Complete',
						text: 'Delete Completed',
						...callback,
					},
				}
			});
		}
	});
}

function isMyData(dataId, showError = true) {
	let result = false;
	$.ajax({
		async: false,
		url: common.API_URI + '/isMyData/' + dataId,
		headers: {
			'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
		},
		dataType: 'json',
		success: function (response, textStatus, jqXHR) {
			result = true;
		},
		error: function (jqXHR, textStatus, errorThrown) {
			if(showError) {
				showAlert({
					type: 'warning',
					text: jqXHR.responseJSON.msg,
				});
			}
		},
	});
	return result;
}
