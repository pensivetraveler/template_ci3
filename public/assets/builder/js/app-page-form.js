let record;
let editors = {};

const FORM_LIFECYCLE = {
    onLoadedLayout : false,
    preparePlugins : false,
    resetFrmInputs : false,
    readyFrmInputs : false,
    fetchFrmValues : false,
    applyFrmValues : false,
    refreshPlugins : false,
    checkFrmValues : false,
    transFrmValues : false,
};

common.FORM_LIFECYCLE = [];
common.FORM_REPEATER = [];
common.DYNAMIC_FIELDS = [];

function updateFormLifeCycle(state, form = null, detail = {}) {
    if(common.FORM_LIFECYCLE[form.getAttribute('name')] === undefined) {
        common.FORM_LIFECYCLE[form.getAttribute('name')] = Object.assign({}, FORM_LIFECYCLE);
    }

    common.FORM_LIFECYCLE[form.getAttribute('name')][state] = true;

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

function onLoadedLayout(form) {
    console.log('onLoadedLayout : ', form.id)
    updateFormLifeCycle('onLoadedLayout', form)
}

function preparePlugins(form) {
    // 옵션 출처 api·script, onload·onchange 콜백 (select2·selectpicker 초기화 전에 옵션을 채운다)
    loadOptionSources(form);

    // select picker
    if ($(form).find('.selectpicker').length) {
        initSelectpicker(form);
    }

    // select2
    if ($(form).find('.select2').length) {
        $(form).find('.select2').each(function () {
            initSelect2(this);
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
                    if(typeof window[cbName] === 'function') window[cbName](this);

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

                        showSwalAlert({
                            type: 'delete',
                            callback: deleteRepeater,
                            params: {repeater, deleteElement},
                        })
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

    // Tagify
    $(form).find('.tagify').each(function () {
        new Tagify(this,
            Object.hasOwn(appPlugins.form, 'tagify') && Object.hasOwn(appPlugins.form.tagify, this.name)
                ? appPlugins.form.tagify[this.name]
                : {
                    // valuesArr 는 [{value:…}, {value:…}, …]
                    originalInputValueFormat: valuesArr => {
                        // ["aa","bb"] 형태의 배열을 JSON 문자열로
                        return valuesArr.map(item => item.value);
                    }
                }
        );
    })

    // Dropzone
    $(form).find('.dropzone-wrapper').each(function () {
        setDropzone(this)
    });

    // Coloris
    $(form).find('.coloris').each(function () {
        this.addEventListener('click', e => {
            Coloris({
                theme: 'polaroid',
                format: 'mixed',
                onChange: (color, inputEl) => {
                    // console.log(`The new color is ${color}`);
                }
            });
        });
    });

    // clipboard
    $(form).find('[clipboard]').each(function () {
        var clipboard = new ClipboardJS(this, {
            target: function(trigger) {
                return trigger; // 클릭된 input 요소를 타겟으로 지정
            }
        });

        clipboard.on('success', function(e) {
            alert('주소가 클립보드에 복사되었습니다.');
            e.clearSelection();
        });

        clipboard.on('error', function(e) {
            alert('자동 복사에 실패했습니다. 직접 복사(Ctrl+C)해 주세요.');
        });
    });

    updateFormLifeCycle('preparePlugins', form)
}

/**
 * resetFrmInputs
 * - form mode 의 값에 관계없이 form input 들에 대한 처리
 * @param form
 * @param fields
 */
function resetFrmInputs(form, fields = []) {
    // value resets
    form.querySelectorAll('input, textarea, select').forEach(function(node) {
        if(
            !isAttributeValueTrue(node, 'data-reset-value')
            || isAttributeValueTrue(node, 'data-default-value')
            || isAttributeValueTrue(node, 'data-set-default-first')
        ) return;

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
                        node.value = '';
                        break;
                    case 'file' :
                        const newNode = node.cloneNode(true);
                        node.parentNode.replaceChild(newNode, node);
                }
                break;
            case 'SELECT' :
                bindSelectValue(node, null)
                break;
            case 'TEXTAREA' :
                node.value = '';
                break;
        }

        // input change attr 초기화
        if(node.type !== 'hidden' && !isAttributeValueTrue(node, 'data-detect-changed')){
            node.setAttribute('data-input-changed', 'false');
        }

        // original value attr 초기화
        if(node.hasAttribute('data-original-value')){
            node.removeAttribute('data-original-value');
        }
    });

    // file type 초기화
    form.querySelectorAll('input[type="file"]').forEach(function (v, i){
        v.value = '';
    })

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
    if($('[data-repeater-type="manual"]').length) {
        $('[data-repeater-type="manual"]').attr('data-repeater-count', 1);
    }

    if ($('[data-repeater-item]').length) {
        $('[data-repeater-item]').each(function (i, v) {
            if(i > 0) $(v).remove();
        });
    }

    // life cycle 초기화
    Object.keys(common.FORM_LIFECYCLE[form.getAttribute('name')]).forEach((v, k) => {
        if(!k) return;
        common.FORM_LIFECYCLE[form.getAttribute('name')][v] = false;
    });

    // form validation reset
    if(typeof fv !== 'undefined') safeReset(fv);

    // 마지막에 폼 전체 및 레코드 리셋
    record = null;

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
function readyFrmInputs(form, mode = null, fields = []) {
    form.querySelectorAll('[data-view-mod]').forEach((node) => {
        // '' 이면 전체 노출, 배열 설정은 PHP 에서 "['add','edit']" 형태의 문자열로 출력됨
        const viewMod = node.getAttribute('data-view-mod').trim();
        if(!viewMod) return;
        const modList = viewMod.startsWith('[')
            ? viewMod.replace(/[\[\]'"\s]/g, '').split(',')
            : viewMod.split('|');
        if(modList.includes(mode)){
            node.closest('.form-validation-unit').classList.remove('d-none');
        }else{
            node.closest('.form-validation-unit').classList.add('d-none');
        }
    });

    form.querySelectorAll('[data-writable="0"]').forEach((node) => {
        node.setAttribute('readonly', 'readonly');
    });

    form.querySelectorAll('[data-editable="0"]').forEach((node) => {
        if(mode === 'edit') {
            node.setAttribute('readonly', 'readonly');
        }else{
            node.removeAttribute('readonly');
        }
    });

    if(form['_mode'] !== undefined) form['_mode'].value = mode;

    if(mode === 'add' || mode === 'filter') {
        const data = {};
        form.querySelectorAll('[data-default-value], [data-set-default-first]').forEach(function (node, i){
            const value = node.getAttribute('data-default-value');
            data[node.dataset.formField] = isNumeric(value)?parseInt(value):value;
        });
        if(Object.keys(data).length) applyFrmValues(form, data, fields, mode === 'filter')
    }

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
function fetchFrmValues(form = null, key = {}, params = {}) {
    let data;

    executeAjax({
        async: false,
        url : getUrlWithIdentifiers(common.API_URI, key, common.API_PARAMS),
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
        error: function (jqXHR, textStatus, errorThrown) {
            console.warn(jqXHR.responseJSON)

            const opt = {
                type: 'warning',
                text: jqXHR.responseJSON.msg,
            };

            if(['view','edit'].includes(form._mode.value)) {
                opt.callback = back;
            }

            showAlert(opt);
        }
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
function applyFrmValues(form, data, fields = [], triggerEvent = true) {
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
        base : initGroupProperties(),
    };
    const groupAttrs = {
        base : {
            group_name : 'base',
        }
    };

    /**
     * classify cloneFields into groups
     */
    cloneFields.forEach((item) => {
        if(item.group && !Object.hasOwn(groups, item.group)) {
            groups[item.group] = initGroupProperties();
            groupAttrs[item.group] = Object.assign(item.group_attributes, {
                group_name : item.group,
            });
        }
        const groupName = item.group ? item.group : 'base';
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
    });

    Object.keys(groups).forEach((groupName, i) => {
        const dto = groups[groupName];

        // group_repeater row 생성
        if(groupAttrs[groupName].group_repeater) {
            // group_repeater type jquery
            // group_repeater type manual: 데이터 개수만큼 행을 만든다
            if(groupAttrs[groupName].repeater_type === 'manual') {
                const wrap = document.querySelector(`[data-repeater-type="manual"][data-group-name="${groupName}"]`);
                if(wrap && data.hasOwnProperty(groupName) && Array.isArray(data[groupName])) {
                    while(wrap.querySelectorAll('[data-repeater-list] > [data-repeater-item]').length < data[groupName].length) {
                        if(!addManualRepeaterRow(wrap)) break;
                    }
                }
            }
            if(groupAttrs[groupName].repeater_type === 'jquery' && $('[data-repeater-type="jquery"]').length) {
                const repeater = document.querySelector(`[data-repeater-type="jquery"][data-group-name="${groupName}"]`);
                const rowCount = parseInt(repeater.getAttribute('data-repeater-count'));
                if(data.hasOwnProperty(groupName)) {
                    for(let i = rowCount+1; i <= data[groupName].length; i++) repeater.querySelector('[data-repeater-create]').click();
                    repeater.setAttribute('data-repeater-count', data[groupName].length === 0 ? 1 : data[groupName].length);
                }
            }
        }

        if(typeof window[`applyFrmValues${pascalize(groupName)}`] === 'function') {
            window[`applyFrmValues${pascalize(groupName)}`](form, data[groupName], groupAttrs[groupName], cloneFields, dto);
        }else{
            Object.keys(dto).map((category) => {
                if(dto[category].length > 0) {
                    if(groupName === 'base') {
                        dto[category].forEach((fieldName) => {
                            applyFrmValuesByCategory(category, groupAttrs[groupName], fieldName, cloneFields, form, data);
                        });
                    }else{
                        if(groupAttrs[groupName].envelope_name) {
                            if(!Object.hasOwn(data, groupName)) return;

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

    if(triggerEvent) {
        updateFormLifeCycle('applyFrmValues', form, {
            record : data,
        });
    }
}

function getFrmInputDto(groupAttrs, field, dataIndex = 0) {
    const groupName = groupAttrs.group_name === 'base'?'':groupAttrs.group_name;

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
                field.id = field.id.replace(regexId, `$1${dataIndex}`);

                regexName = new RegExp(`(${field.field}\\[)\\d+(\\])`);
                field.name = field.name.replace(regexName, `$1${dataIndex}$2`);
            }
            return field;
        }
    }
}

function applyFrmValuesByCategory(category, groupAttr, fieldName, fields, form, data, dataIndex = 0) {
    if(!data) return;
    const groupName = groupAttr.group_name;
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
                case 'select2':
                    if(Object.hasOwn(appPlugins.form, 'select2') &&
                        Object.hasOwn(appPlugins.form.select2, name) &&
                        Object.hasOwn(appPlugins.form.select2[name], 'beforeHand')
                    ) {
                        appPlugins.form.select2[name].beforeHand();
                    }
                    break;
            }

            if (form[name] && Object.hasOwn(data, fieldName)) {
                bindSelectValue(form[name], data[fieldName]);
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
                case 'onoff' :
                    if(!data[fieldName]) return;
                    form.querySelectorAll(`[name="${name}"]`).forEach((input) => {
                        input.checked = input.value == data[fieldName];
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
                case 'base' :
                case 'thumbnail' :
                case 'single' :
                case 'multiple' :
                case 'readonly' :
                    // setFormListItem(`#${id}-list`, data[fieldName], field);
                    break;
                default :
                    if(data[fieldName] === undefined) console.warn(`applyFrmValues : data doesn't have '${name}'.`);
                    if(form[name] === undefined) console.warn(`applyFrmValues : form doesn't have '${name}'.`);
                    // file input 에 문자열을 넣으면 InvalidStateError
                    if(data[fieldName] !== undefined && form[name] !== undefined && form[name].type !== 'file')
                        form[name].value = data[fieldName];
                    break;
            }
            break;
        case 'customs' :
            if(typeof window[`applyFrmValuesCustom${pascalize(fieldName)}`] === "function") {
                window[`applyFrmValuesCustom${pascalize(fieldName)}`](form, data[fieldName]);
            }else{
                switch (field.subtype) {
                    case 'tag' :
                        form[name].value = data[fieldName];
                        break;
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
    }

    // 수정 화면에서 값이 그대로면 중복 검사를 통과하도록 원래 값을 기록 (중복 확인 버튼이 없는 is_unique 필드 포함)
    const hasUniqueRule = typeof field.rules === 'string' && /(^|\|)is_unique\[/.test(field.rules);
    if((field.form_attributes.with_btn && field.form_attributes.btn_type === 'dup_check') || hasUniqueRule) {
        if(form[name] && data[fieldName] !== undefined && data[fieldName] !== null) {
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

function refreshPlugins(form, data = null) {
    // selectpicker
    $(form).find('select.selectpicker').each(function() {
        const select = this;
        const $select = $(select);

        if (select.dataset.editable === undefined) return;

        const shouldDisabled =
            parseInt(select.dataset.editable, 10) === 0 &&
            form._mode.value === 'edit';

        /*
         * 이미 원하는 상태라면 처리하지 않는다.
         */
        if ($select.prop('disabled') === shouldDisabled) return;

        /*
         * 원본 select 상태 변경
         */
        $select.prop('disabled', shouldDisabled);
        syncSelectpickerDisabled(select);
    });

    // textarea-autosize
    if($(form).find('.textarea-autosize').length) {
        $(form).find('.textarea-autosize').each(function() {
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
    if($(form).find('.textarea-quill').length) {
        $(form).find('.textarea-quill').each(function() {
            editors[this.id] = new Quill(`#${this.id}`, {
                bounds: `#${this.id}`,
                placeholder: 'Type Something...',
                modules: {
                    formula: true,
                    toolbar: toolbarDefault.quill
                },
                theme: 'snow'
            });

            // 내용이 바뀔 때마다 hidden 입력에 넣는다 (전에는 keydown 만 봐서 붙여넣기·툴바 서식이 빠질 수 있었음)
            const editorId = this.id;
            editors[editorId].on('text-change', function() {
                $(`[data-textarea-id="${editorId}"]`).val(editors[editorId].root.innerHTML);
            });
        })
    }

    $(form).find('.form-list-item-wrap').each(function() {
        if(!this.children.length) {
            this.classList.add('d-none');
        }
    });

    // coloris
    $(form).find('.form-input_color-coloris').each(function () {
        if(this.value) {
            setColorisValue(`#${this.id}`, this.value);
        }
    });

    updateFormLifeCycle('refreshPlugins', form, {
        record : data,
    });
}

function setFormListItem(selector, data, field) {
    if(!isValidSelector(selector) || document.querySelector(selector) === null) return;

    let html = '';
    const key = field.form_attributes.list_field ?? field.field;
    if(!isEmpty(data[key])) {
        let list = [];
        isArray(data[key]) ? list = data[key] : list.push(data[key]);
        list.forEach((item) => {
            if(common.IDENTIFIER && Object.hasOwn(data, common.IDENTIFIER)) item[common.IDENTIFIER] = data[common.IDENTIFIER];
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
    const fullItem = JSON.stringify(item).replace(/"/g, "'");
    const articleId = item.article_id ?? '';
    const fileId = item.file_id;
    if(Object.hasOwn(field.form_attributes, 'list_sorter') && field.form_attributes.list_sorter) {
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
        const url = common.BASE_URI+'/common';
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
    const url = common.BASE_URI+'/common';
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
                <button class="btn btn-danger waves-effect p-1" type="button" onclick="deleteFile(this, '${field.form_attributes.list_delete || field.field}')">
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

function prepareSelectOptions(node, data = []) {
    if (!node) return;
    const $node = $(node);

    // 1) <select> 안의 기존 <option> 전부 제거
    $node.empty();

    if ($node.hasClass('select2') || $node.hasClass('select2-repeater'))
        setSelectEmptyOption($node);

    // 2) 새로 가져온 row마다 <option> 생성
    if (!Array.isArray(data)) {
        for(const key of Object.keys(data)) {
            const option = new Option(data[key], key, false, false);
            $node.append(option);
        }
    }else{
        data.forEach(function(row) {
            const option = new Option(row.text, row.id, false, false);
            $node.append(option);
        });
    }

    // 3) 값 존재 확인
    const isFirstOptionSelected = isAttributeValueTrue(node, 'data-set-default-first');
    let originalValue = node.dataset.originalValue??''; // 원래 선택값
    let valueInOptions = false;
    let firstOption = true;

    $node.find('option').each(function(i,option) {
        if(option.value === '') return;

        if(originalValue === '') {
            if(firstOption && isFirstOptionSelected) {
                node.dataset.defaultValue = originalValue = option.value;
                firstOption = false;
            }
        }

        if(String(option.value) === originalValue) valueInOptions = true;
    });

    if ($node.hasClass('selectpicker')) {
        if (!$node.find('option').length) {
            $node.prepend(
                `<option value="" disabled>${getLocale(
                    'No Option Available',
                    common.LOCALE
                )}</option>`
            );
        }

        rebuildSelectpicker(node);
    }

    // 4) select2에게 "내부 <select>이 바뀌었음" 알리기
    bindSelectValue(
        node,
        valueInOptions ? originalValue : null,
        'handler'
    );
}

function setSelectEmptyOption($node) {
    const isMultiple = $node.prop('multiple');
    let defaultValue = $node.val();
    if(isMultiple) {
        // multiple select2에서는 빈 option을 사용하지 않는다.
        $node.find('option[value=""]').remove();

        // 빈 값이 섞여 있다면 제거
        if (Array.isArray(defaultValue)) {
            defaultValue = defaultValue.filter(v => v !== '');
            $node.val(defaultValue.length ? defaultValue : null);
        } else {
            $node.val(null);
        }
    } else {
        // single select2에서는 placeholder 표현을 위해 빈 option이 필요하다.
        if (!$node.find('option[value=""]').length) {
            $node.prepend('<option value=""></option>');
        }
    }

}

function initSelect2(node, addOption = {}) {
    const $this = $(node);

    if ($this.data('select2')) return;

    setSelectEmptyOption($this);

    focusSelect2($this);

    const option = {
        allowClear: !node.dataset.defaultValue,
        placeholder: $this.attr('placeholder'),
        dropdownParent: $this.parent(),
        processResults: function(json) {
            return {
                results: json.data
                    .filter(row => row.id != null && row.text && row.text.trim() !== '')
                    .map(row => ({
                        id:   row.id,
                        text: row.text
                    }))
            };
        }
    }

    let addHandler = node.dataset.changeAfter !== undefined;

    const onHandler = {
        change(e) {
            if (node.dataset.changeAfter) {
                const changeAfter = JSON.parse(node.dataset.changeAfter.replace(/'/g, '"'));
                if (window[changeAfter.callback]) {
                    window[changeAfter.callback](node, changeAfter.params ?? {});
                }
            }
        },
        'select2:select'(e) {
            // 필요 시 처리
        }
    };

    if(Object.hasOwn(appPlugins.form, 'select2') && Object.hasOwn(appPlugins.form.select2, node.name)) {
        for(const key of Object.keys(appPlugins.form.select2[node.name])) {
            if(key === 'onHandler') {
                addHandler = true;
                for(const handlerKey of Object.keys(appPlugins.form.select2[node.name][key])) {
                    onHandler[handlerKey] = appPlugins.form.select2[node.name][key][handlerKey];
                }
            }else{
                option[key] = appPlugins.form.select2[node.name][key];
            }
        }
    }

    if(Object.keys(addOption).length) {
        Object.assign(option, addOption);
    }

    $this
        .wrap('<div class="position-relative w-100"></div>')
        .select2(option)
        .off('change.formSelect2 select2:select.formSelect2')
        .on('select2:select.formSelect2', onHandler['select2:select']);

    if(addHandler) {
        $this.on('change.formSelect2', onHandler.change);
    }
}

function setDynamicSelectOptions(selector, params) {
    const node = document.querySelector(selector);
    if(!node.value) {
        prepareSelectOptions(document.querySelector(params.target))
        return;
    }

    const data = {
        _mode: 'option',
        [node.dataset.formField] : node.value
    }

    if(Object.hasOwn(params, 'render')) {
        data.render = params.render;
    }

    let url;
    if(Object.hasOwn(params, 'url')) {
        url = params.url;
    }else if(Object.hasOwn(params, 'add_uri')){
        url = common.BASE_URI.replace(/\/+$/, '') + '/' + String(params.add_uri).replace(/^\/+/, '');
    }else{
        url = common.API_URI + '/options';
    }

    // 2) AJAX로 method 리스트를 가져온다
    // API 키 검사가 켜진 환경에서도 받도록 Authorization 헤더를 붙인다
    $.ajax({url, data, dataType: 'json', headers: {'Authorization': common.HOOK_PHPTOJS_VAR_TOKEN}})
        .done(function(json) {
            if (!json || !Object.hasOwn(json, 'data'))
                console.warn('data가 반환되지 않음', json);

            prepareSelectOptions(document.querySelector(params.target), json.data)
        })
        .fail(function() {
            console.error(`${params.target} 리스트 로드 실패`);
        });
}

// select2 option selected 유동적 부여
function reloadSelect2(selector, url, preselectedIds = [], template = {id:'id',text:'text'}) {
    // 비워주기
    const $sel = $(selector);
    $sel.val(null);
    $sel.empty(); // 옵션 싹 비움

    $.ajax({url, dataType: 'json', headers: {'Authorization': common.HOOK_PHPTOJS_VAR_TOKEN}}).done(function (json) {
        const rows = json.data || [];
        rows.forEach(function (row) {
            const id   = row[template.id];
            const text = row[template.text];
            const isSelected = Array.isArray(preselectedIds)
                ? preselectedIds.includes(id)
                : preselectedIds === id;

            // new Option(text, value, defaultSelected, selected)
            const opt = new Option(text, id, isSelected, isSelected);
            $sel.append(opt);
        });

        // 다 채운 후 Select2 갱신
        $sel.trigger('change.formSelect2');
    });
}

function resetSelect2(selector, removeOptions = false) {
    const defaultValue = $(selector).attr('data-default-value')??null;
    $(selector).val(defaultValue).trigger('change')
    if(removeOptions) $(selector).empty();
}

function setSelect2Readonly(selector, reverse = false) {
    if(reverse) {
        document.querySelector(selector).classList.remove('readonly');
    }else{
        document.querySelector(selector).classList.add('readonly');
    }
}

/**
 * Dynamic Fields 관련 methods
 */
function addDynamicField(fvInstance, name, options) {
    const form = fvInstance.form; // 또는 document.querySelector('form');
    const formName = form.getAttribute('name');

    if(common.DYNAMIC_FIELDS[formName] === undefined) {
        common.DYNAMIC_FIELDS[formName] = new Set();
    }

    // 이미 등록되어 있으면 중복 추가 방지
    if (!Object.hasOwn(fvInstance.getFields(), name)) {
        fvInstance.addField(name, options);
        common.DYNAMIC_FIELDS[formName].add(name);
    }
}

// 2) 안전 리셋: 동적 필드 먼저 정리 후 전체 resetForm
function safeReset(fvInstance) {
    const form = fvInstance.form; // 또는 document.querySelector('form');
    const formName = form.getAttribute('name');

    if(common.DYNAMIC_FIELDS[formName] !== undefined) {
        for (const name of common.DYNAMIC_FIELDS[formName]) {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el || common.DYNAMIC_FIELDS[formName]) {
                // DOM에 없거나 완전히 제거하고 싶다면 FV에서 먼저 제거
                fvInstance.removeField(name);
            } else {
                // DOM에는 남겨두고 값만 초기화하고 싶다면 필드 단위 리셋
                // true = 값까지 초기화
                fvInstance.resetField(name, true);
            }
        }
        common.DYNAMIC_FIELDS[formName].clear();
    }

    // 마지막에 폼 전체 리셋
    fvInstance.resetForm(true); // 값/상태/메시지 초기화
}

function getFilterData(selector, isFirstDraw = null) {
    if(!$(selector).length) return {};

    const filters = {
        where : {},
        whereIn : {},
        like : [],
        date : {
            start_date : null,
            end_date : null,
            on_date : null,
            date_range: null,
        },
        text : {},
    };

    if(isFirstDraw !== null && isFirstDraw) {
        $(selector).find('[data-default-value], [data-set-default-first]').each(function (i, node) {
            const value = node.dataset.defaultValue;
            const match = node.name.match(/^([^\[]+)\[([^\]]+)\]$/);
            if (match) {
                const group = match[1]; // where
                const key = match[2];   // partner_id

                if(group === 'like') {
                    // like 는 {field, value} 목록 (배열에 문자열 키를 넣으면 직렬화에서 사라짐)
                    if(key === 'value') {
                        filters.like.push({
                            field: $(selector).find('[name="like[field]"]').val() ?? null,
                            value: value,
                        });
                    }else if(key !== 'field') {
                        filters.like.push({
                            field: key,
                            value: value,
                        });
                    }
                }else if(group === 'where' && $(node).is('select[multiple]')) {
                    filters.whereIn[key] = [value].filter(v => !isEmpty(v));
                }else if(Object.hasOwn(filters, group)) {
                    filters[group][key] = isNumeric(value)?parseInt(value):value;
                }
            }
        });
    }else if($(selector).find('[name="_onloaded"]').val() === '1') {
        filters.date.start_date = $(selector).find('[name="date[start_date]"]').val() ?? null;
        filters.date.end_date = $(selector).find('[name="date[end_date]"]').val() ?? null;
        filters.date.on_date = $(selector).find('[name="date[on_date]"]').val() ?? null;
        filters.date.date_range = $(selector).find('[name="date[date_range]"]').val() ?? null;

        $(selector).find('[name^="like"]').each(function() {
            const match = this.name.match(/\[(.*?)\]/);
            if(match) {
                const key = match[1];
                if(key === 'value') {
                    filters.like.push({
                        field: $(selector).find('[name="like[field]"]').val() ?? null,
                        value: $(selector).find('[name="like[value]"]').val() ?? null,
                    })
                }else if(key !== 'field'){
                    filters.like.push({
                        field: key,
                        value: this.value,
                    })
                }
            }
        });

        $(selector).find('[name^="where"]').each(function() {
            const match = this.name.match(/\[(.*?)\]/);

            if (!match) return;

            const key = match[1];
            const $field = $(this);

            let value = $field.val()??this.value;

            if ($field.is('select[multiple]')) {
                value = value ?? [];

                if (!Array.isArray(value)) {
                    value = [value];
                }

                value = value.filter(v => !isEmpty(v));
                filters.whereIn[key] = value ?? null;
            } else {
                filters.where[key] = value ?? null;
            }
        });
    }

    return filters;
}

function activateFilterForm(selector = null, callback = undefined, params = undefined) {
    if(selector === null) selector = '#formFilter';

    if($(selector).length > 0) {
        const loaded = parseInt(document.querySelector(`${selector} [name="_onloaded"]`).value);
        if(!loaded) {
            $(selector).on('preparePlugins', (e) => {
                const form = document.getElementById(e.detail.formSelector);
                const node = form?.querySelector('[name="_onloaded"]');
                if(node) node.value = 1;
            });

            onLoadedLayout(document.querySelector(selector));
            preparePlugins(document.querySelector(selector));
            resetFrmInputs(document.querySelector(selector), common.FILTER_COLUMNS);
            readyFrmInputs(document.querySelector(selector), 'filter', common.FILTER_COLUMNS);
            refreshPlugins(document.querySelector(selector));

            $('.form-type-filter').find('.btn-search').on('click', function(e) {
                e.preventDefault();
                const start_date = $(selector).find('[name="date[start_date]"]').val() ?? null;
                const end_date = $(selector).find('[name="date[end_date]"]').val() ?? null;
                if(start_date !== null && end_date !== null) {
                    if(new Date(start_date) > new Date(end_date)) {
                        showAlert({
                            type: 'warning',
                            text: getLocale('End Date must be on or after Start Date', common.LOCALE),
                        });
                        return;
                    }
                }

                window.dispatchEvent(
                    new CustomEvent('filterSearchClicked', {
                        bubbles : false,
                        cancelable : true,
                        composed : false,
                    }),
                );

                if(typeof callback === 'function') callback(params);
            });

            $('.form-type-filter').find('.btn-reset').on('click', function(e) {
                e.preventDefault();
                resetFrmInputs(document.querySelector(selector), common.FILTER_COLUMNS);
                readyFrmInputs(document.querySelector(selector), 'filter', common.FILTER_COLUMNS);
                refreshPlugins(document.querySelector(selector));

                window.dispatchEvent(
                    new CustomEvent('filterResetClicked', {
                        bubbles : false,
                        cancelable : true,
                        composed : false,
                    }),
                );

                if(typeof callback === 'function') callback(params);
            });
        }
    }
}

/**
 * 리피터 manual (jQuery 리피터 플러그인 없이 직접 추가·삭제, form_side_group_repeater_manual.php)
 * 새 행은 <template data-manual-repeater-template> 의 __INDEX__ 를 다음 행 번호로 바꿔 붙인다.
 * 행 번호는 지운 뒤에도 다시 매기지 않는다 (서버는 group[번호][필드] 를 순서대로 받음)
 */
function addManualRepeaterRow(wrap) {
    const list = wrap.querySelector('[data-repeater-list]');
    const template = wrap.querySelector('template[data-manual-repeater-template]');
    if(!list || !template) return null;

    let index = 0;
    list.querySelectorAll(':scope > [data-repeater-item]').forEach((row) => {
        const rowIndex = parseInt(row.getAttribute('data-row-index'), 10);
        if(!isNaN(rowIndex) && rowIndex >= index) index = rowIndex + 1;
    });

    const holder = document.createElement('div');
    holder.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index)).trim();
    const row = holder.firstElementChild;
    if(!row) return null;
    list.appendChild(row);
    wrap.setAttribute('data-repeater-count', list.querySelectorAll(':scope > [data-repeater-item]').length);

    window.dispatchEvent(new CustomEvent('addRepeaterItem', {
        bubbles : false,
        cancelable : true,
        composed : false,
        detail : { node : row },
    }));

    const groupName = wrap.getAttribute('data-group-name');
    const cbName = camelize(`after_${groupName}_repeater_show`);
    if(typeof window[cbName] === 'function') window[cbName](row);

    return row;
}

function removeManualRepeaterRow(row) {
    const wrap = row.closest('[data-repeater-type="manual"]');
    const removeRow = function() {
        row.remove();
        if(wrap) wrap.setAttribute('data-repeater-count', wrap.querySelectorAll('[data-repeater-list] > [data-repeater-item]').length);
    };

    // 저장된 행(수정 화면이고 repeater_id 값이 있음)은 확인 후 서버 삭제(deleteRepeater), 아니면 화면에서만
    const form = row.closest('form');
    const identifier = form && form[common.IDENTIFIER] ? form[common.IDENTIFIER].value : '';
    const repeaterId = wrap ? wrap.getAttribute('data-repeater-id') : '';
    const idInput = repeaterId ? row.querySelector(`[data-group-field="${repeaterId}"]`) : null;
    if(!identifier || !idInput || idInput.value === '') {
        removeRow();
        return;
    }

    showSwalAlert({
        type: 'delete',
        callback: deleteRepeater,
        params: {repeater: row, deleteElement: removeRow},
    });
}

$(document).on('click', '[data-repeater-type="manual"] [data-manual-repeater-add]', function() {
    addManualRepeaterRow(this.closest('[data-repeater-type="manual"]'));
});

$(document).on('click', '[data-repeater-type="manual"] [data-manual-repeater-delete]', function() {
    removeManualRepeaterRow(this.closest('[data-repeater-item]'));
});

/**
 * option_attributes 의 브라우저 쪽 옵션 (builder_form_helper get_admin_form_attributes 가 data-option-* 로 넣음)
 * - option_type api: option_data.url(BASE_URI 기준, 없으면 화면 API 의 /options)·method·params 로 요청(_mode=option, render)
 * - option_type script: window[option_data.method](node, params) 가 돌려준 목록({값: 라벨} 또는 [{id, text}])
 * - data-option-extra: 옵션 값별 추가 값 → <option data-{컬럼}> (option_data.select.extra)
 * - render.callback: 옵션마다 window[이름](option, params ?? node) — false 를 돌려주면 그 옵션을 빼고, 문자열이면 라벨로 씀
 * - onload: 옵션을 채운 뒤 window[이름](params ?? node), onchange: 값이 바뀔 때 window[이름](params ?? node, event)
 * 값 채우기(applyFrmValues)보다 먼저 끝나도록 동기 요청을 쓴다 (executeAjax 기본)
 */
function getOptionCallback(node, name) {
    const fn = node.getAttribute(`data-option-${name}`);
    if(!fn) return null;
    let params;
    const raw = node.getAttribute(`data-option-${name}-params`);
    if(raw !== null) {
        try { params = JSON.parse(raw); } catch (e) { params = raw; }
    }
    if(typeof window[fn] !== 'function') {
        console.warn(`option ${name} '${fn}' is not defined`);
        return null;
    }
    return {fn: window[fn], params: params};
}

function decorateOptions(node) {
    let extras = {};
    try { extras = JSON.parse(node.getAttribute('data-option-extra') || '{}') || {}; } catch (e) { extras = {}; }
    const render = getOptionCallback(node, 'render');

    Array.from(node.options || []).forEach(function(option) {
        const extra = extras[option.value];
        if(extra && typeof extra === 'object') {
            Object.keys(extra).forEach((key) => option.setAttribute(`data-${key.replace(/_/g, '-')}`, extra[key] ?? ''));
        }
        if(render) {
            const result = render.fn(option, render.params !== undefined ? render.params : node);
            if(result === false) option.remove();
            else if(typeof result === 'string') option.text = result;
        }
    });
}

function loadOptionSources(form) {
    if(!form) return;
    form.querySelectorAll('[data-option-type], [data-option-onload], [data-option-onchange], [data-option-render], [data-option-extra]').forEach(function(node) {
        if(node.dataset.optionLoaded === '1') return;
        node.dataset.optionLoaded = '1';

        const type = node.getAttribute('data-option-type');
        if(type === 'api') {
            let params = {};
            let render = {};
            try { params = JSON.parse(node.getAttribute('data-option-params') || '{}') || {}; } catch (e) { params = {}; }
            try { render = JSON.parse(node.getAttribute('data-option-render') || '{}') || {}; } catch (e) { render = {}; }
            const path = String(node.getAttribute('data-option-url') || '');
            const url = path === ''
                ? common.API_URI + '/options'
                : (/^https?:\/\//.test(path) ? path : common.BASE_URI.replace(/\/+$/, '') + '/' + path.replace(/^\/+/, ''));
            executeAjax({
                url: url,
                method: node.getAttribute('data-option-method') === 'post' ? 'post' : 'get',
                headers: {
                    'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
                },
                data: Object.assign({_mode: 'option'}, params, Object.keys(render).length ? {render: render} : {}),
                success: function(json) {
                    prepareSelectOptions(node, normalizeOptionResponse(json && json.data ? json.data : []));
                },
                error: function() {
                    console.error(`${node.id} 옵션 로드 실패`);
                },
            });
        }else if(type === 'script') {
            const script = getOptionCallback(node, 'function');
            if(script) prepareSelectOptions(node, script.fn(node, script.params) || []);
        }

        decorateOptions(node);

        const onload = getOptionCallback(node, 'onload');
        if(onload) onload.fn(onload.params !== undefined ? onload.params : node);

        const onchange = getOptionCallback(node, 'onchange');
        if(onchange) {
            node.addEventListener('change', function(event) {
                onchange.fn(onchange.params !== undefined ? onchange.params : node, event);
            });
        }
    });
}

/**
 * 옵션 응답 모양을 prepareSelectOptions 가 받는 형태로: [{id, text}] 는 그대로,
 * 공통 options API(_mode=option)처럼 {값: 라벨} 하나가 배열에 감싸여 오면([{1: 'a'}]) 그 객체를 쓴다
 */
function normalizeOptionResponse(data) {
    if(Array.isArray(data) && data.length === 1 && data[0] !== null && typeof data[0] === 'object'
        && !Object.hasOwn(data[0], 'id') && !Object.hasOwn(data[0], 'text')) {
        return data[0];
    }
    return data;
}

