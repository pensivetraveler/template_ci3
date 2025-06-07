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

function getData(key = null, params = {}) {
    if(!key) throw new Error(`key is not defined`);

    let dataParams;
    if(Object.keys(common.API_PARAMS).length === 0){
        dataParams = params;
    }else{
        dataParams = { ...common.API_PARAMS, ...params };
    }

    let data = null;
    executeAjax({
        async: false,
        url : getUrlWithIdentifiers(common.API_URI, key, dataParams),
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
                url: getUrlWithIdentifiers(common.API_URI, dataId, common.API_PARAMS),
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
    executeAjax({
        async: false,
        url: getUrlWithIdentifiers(common.API_URI + '/isMyData', dataId),
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

function logout() {
    let result = false;
    executeAjax({
        async: false,
        method: 'post',
        url: common.API_BASE_URI + '/auth/logout',
        headers: {
            'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
        },
        dataType: 'json',
        success: function (response, textStatus, jqXHR) {
            redirect(common.BASE_URI);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log(jqXHR)
            showAlert({
                type: 'warning',
                text: jqXHR.responseJSON.msg,
            });
        },
    });
    return result;
}

function getIdentifiersData(data, identifiers = [], asString = false) {
    const obj = {};
    const isDomObj = data instanceof Node;
    const isForm = isDomObj&&data.tagName === 'FORM';

    if(isDomObj&&!isForm) {
        console.warn('data is not be able to parsed', data);
        return obj;
    }

    if(identifiers.length) {
        for(const field of identifiers) {
            obj[field] = isForm?data[field].value:data[field];
        }
    }
    return asString?JSON.stringify(obj):obj;
}

function getIdentifiersDataFromForm(form, identifiers = [], asString = false) {
    const obj = {};
    if(identifiers.length) {
        for(const field of identifiers) {
            if(form[field] === undefined) continue;
            obj[field] = form[field].value;
        }
    }
    return asString?JSON.stringify(obj):obj;
}

function getUrlWithIdentifiers(url, identifierData = {}, addParams = {}) {
    if(isObject(identifierData)){
        if(Object.keys(identifierData).length) {
            if(Object.keys(identifierData).length === 1 && !isNaN(Object.values(identifierData)[0])){
                url += '/' + Object.values(identifierData)[0];
            }else{
                Object.assign(addParams, identifierData);
            }
        }
    }else{
        url += '/' + identifierData;
    }

    if(Object.keys(addParams).length)
        url += (url.indexOf('?') === -1 ? '?' : '&') + new URLSearchParams(addParams).toString();

    return url;
}

function getRedirectActionData(node, identifiers = []) {
    const obj = {};
    if(identifiers.length) {
        if($(node).data('id') === undefined){
            for(const field of identifiers) obj[field] = $(node).data(field);
        }else{
            obj[identifiers[0]] = $(node).data('id');
        }
    }
    return obj;
}

function getRedirectActionUrl(button, url, identifiers = []) {
    return getUrlWithIdentifiers(url, getRedirectActionData(button, identifiers));
}