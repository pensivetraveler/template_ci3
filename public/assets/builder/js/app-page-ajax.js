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

function submitAjax(selector, options = {}, queryString = false, test = false) {
	const form = document.querySelector(selector);
	const formData = options.hasOwnProperty('data') ? options.data : getFormData(form);

	let url;
	if(!options.hasOwnProperty('url')) {
		url = common.API_URI;
		if(common.hasOwnProperty('API_URI_ADD')&&common.API_URI_ADD.length>0) url += '/'+common.API_URI_ADD;
		url = getUrlWithIdentifiers(url, getIdentifiersData(form, common.IDENTIFIER), common.API_PARAMS)
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