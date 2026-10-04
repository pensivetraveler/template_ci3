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
            result = Object.hasOwn(locale, key) ? result[key] : undefined;
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

function getSwalOption(obj) {
    let title, text;
    if(obj.type === undefined) obj.type = 'success';
    if(!['success', 'warning', 'error', 'delete', 'confirm'].includes(obj.type)) throw new Error(`showAlert : Type is not allowed. ${obj.type}`);
    if(obj.title !== undefined) title = getLocale(obj.title, common.LOCALE);
    if(obj.text !== undefined || obj.html !== undefined) text = getLocale(obj.text, common.LOCALE);

    switch (obj.type) {
        case 'delete' :
            if(title === undefined) title = getLocale('Do you really want to delete?', common.LOCALE);
            if(text === undefined) text = getLocale('You can\'t undo this action', common.LOCALE);
            obj = Object.assign(obj, {
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: getLocale('Delete', common.LOCALE),
                cancelButtonText: getLocale('Cancel', common.LOCALE),
                customClass: {
                    confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
                    cancelButton: 'btn btn-outline-secondary waves-effect'
                },
                buttonsStyling: false
            });
            break;
        case 'confirm' :
            obj.type = 'warning';
            if(title === undefined) title = 'Warning!';
            if(text === undefined) text = getLocale('Are you sure you want to do this?', common.LOCALE);
            break;
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

    let html = null;
    if(obj.text || obj.html) html = nl2br(obj.text??obj.html);

    return Object.assign({
        title: obj.title,
        html: html,
        icon: obj.icon??obj.type,
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
    }, obj);}

function showSwalAlert(obj = {}) {
    const option = getSwalOption(obj);
    Swal.fire(option).then(function (result) {
        if(result.isConfirmed) {
            if(option.callback !== undefined) {
                if(Object.hasOwn(option, 'params') && option.params !== null){
                    option.params.result = result;
                    callUserFunc(option.callback, option.params);
                }else{
                    option.callback(result);
                }
            }else{
                // if(obj.type === 'error') location.reload();
            }
        }
    });
}

function reformatFormData(form, data, regexp = {}, side = false) {
    if(data === undefined) {
        data = [];
        for(const input of form.querySelectorAll('input')) {
            if(input.dataset.validate === 'false') continue;
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

        if (curr.form_attributes?.form_validation === false) {
            return acc;
        }

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
                        if(Object.hasOwn(curr.errors, rule)) item.validators['regexp'].message = curr.errors[rule];
                    }else{
                        console.warn(`reformatFormData : ${rule} validator is not set.`);
                    }
                }
            }
        });

        if (Object.keys(item.validators).length === 0) return acc;

        acc[curr.field] = item;
        return acc;
    }, {});
}

function setFlatpickr(node) {
    if (!node) return;

    // 이미 초기화된 경우 중복 초기화 방지
    if (node._flatpickr) {
        node._flatpickr.destroy();
    }

    const repositionCalendar = function (instance) {
        const calendar = instance.calendarContainer;
        const input = instance.input;
        const parent = calendar.parentElement;

        if (!calendar || !input || !parent) return;

        const inputRect = input.getBoundingClientRect();
        const parentRect = parent.getBoundingClientRect();

        calendar.style.position = 'absolute';
        calendar.style.top = `${inputRect.bottom - parentRect.top + 4}px`;
        calendar.style.left = `${inputRect.left - parentRect.left}px`;
        calendar.style.right = 'auto';
    };

    // See https://flatpickr.js.org/formatting/
    const option = {
        dateFormat: 'Y-m-d',
        positionElement: node,
        position: 'below left',
        onReady: function(selectedDates, dateStr, instance) {
            // Center the popup relative to the input
            instance.calendarContainer.classList.add('flatpickr-side-position');
        },
        onOpen: function (selectedDates, dateStr, instance) {
            repositionCalendar(instance);
        },
        onMonthChange: function (selectedDates, dateStr, instance) {
            repositionCalendar(instance);
        },
        onYearChange: function (selectedDates, dateStr, instance) {
            repositionCalendar(instance);
        },
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
            dateFormat: 'h:i K',
            // time_24hr: false,

            onReady: function (selectedDates, dateStr, instance) {
                const timeContainer = instance.timeContainer;

                if (timeContainer && instance.amPM) {
                    timeContainer.insertBefore(instance.amPM, timeContainer.firstChild);
                }

                instance.calendarContainer.classList.add('flatpickr-side-position');
            },

            onOpen: function (selectedDates, dateStr, instance) {
                repositionCalendar(instance);
            },
        })
    }else if (node.classList.contains('flatpickr-year-month')) {
        Object.assign(option, {
            locale: 'ko',
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: 'Y.m',
                    altFormat: 'Y년 m월',
                })
            ],
        });
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
    }else if(node.classList.contains('cleave-bizno')) {
        new Cleave(node, {
            delimiter: '-',
            blocks: [3, 2, 5],
            uppercase: false,
            numericOnly: true
        });
    }
}

function replaceValidationMessage(message, obj = null) {
    for(const key of Object.keys(obj)) {
        message = message.replaceAll(`{${key}}`, obj[key]);
    }
    return window.Josa.s(message);
}

function replaceValidationMessage(message, obj) {
    for(const key of Object.keys(obj)) {
        message = message.replaceAll(`{${key}}`, obj[key]);
    }
    return window.Josa.s(message);
}

function setDropzone(node, addOptions = {}, previewTemplate = '') {
    console.log(node)
    if(window['Dropzone'] === undefined) {
        console.warn('Dropzone lib is not loaded');
    }else {
        if(node === undefined){
            console.warn('Check the Dropzone target');
        }else{
            // Dropzone 자동 탐색 방지
            window['Dropzone'].autoDiscover = false;

            if(previewTemplate === '') {
                previewTemplate = `<div class="dz-preview dz-file-preview">
<div class="dz-details">
  <div class="dz-thumbnail">
    <img data-dz-thumbnail>
    <span class="dz-nopreview">No preview</span>
    <div class="dz-success-mark"></div>
    <div class="dz-error-mark"></div>
    <div class="dz-error-message"><span data-dz-errormessage></span></div>
    <div class="progress">
      <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
    </div>
  </div>
  <div class="dz-filename" data-dz-name></div>
  <div class="dz-size" data-dz-size></div>
</div>
</div>`;
            }

            let baseOptions = Object.assign([...node.attributes]
                    .filter(attr => attr.name.startsWith('dz-') && attr.value !== '')
                    .reduce((acc, attr) => {
                        const name = camelize(attr.name.replace('dz-',''));
                        acc[name] = isNaN(attr.value)?attr.value:parseInt(attr.value);
                        return acc;
                    }, {}),
                addOptions
            );
            console.log(baseOptions)

            new Dropzone(node,
                Object.assign({
                    url: node.closest('form').action,
                    autoProcessQueue: true,
                    thumbnailWidth: null,
                    thumbnailHeight: null,
                    previewTemplate: previewTemplate,
                    parallelUploads: 1,
                    addRemoveLinks: true,
                    dictDefaultMessage: getLocale('Drop files here or click to upload', common.LOCALE),
                    dictRemoveFile: getLocale('Delete', common.LOCALE),
                    init: function () {
                        this.on("success", function (file, response) {
                            console.log("업로드 성공:", response);
                        });
                        this.on("error", function (file, errorMessage) {
                            console.log("업로드 실패:", errorMessage);
                        });
                        this.on('addedfile', function (file) {
                            console.log("파일 추가");

                            // if (this.files.length > 1) {
                            // 	this.removeFile(this.files[0]);
                            // }

                            const previewContainer = this.element.querySelector('.dropzone-file-container');
                            previewContainer.appendChild(file.previewElement); // 이미지 미리보기만 추가
                        });
                        this.on("sending", function (file, errorMessage) {
                            console.log("업로드");
                        });
                    },
                }, baseOptions)
            );
        }
    }
}

function setColorisValue(selector, color) {
    const input = document.querySelector(selector);

    if (!input) {
        return;
    }

    input.value = color;

    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

function initSelectpicker(scope) {
    const $scope = scope ? $(scope) : $(document);

    const $selects = $scope.is('select.selectpicker')
        ? $scope
        : $scope.find('select.selectpicker');

    $selects.each(function () {
        const $select = $(this);

        if(this.dataset.changeAfter){
            const changeAfter = JSON.parse(this.dataset.changeAfter.replace(/'/g, '"'));

            if(window[changeAfter.callback]) {
                $select
                    .off('changed.bs.select.formSelectpicker')
                    .on('changed.bs.select.formSelectpicker', () => {
                    window[changeAfter.callback](this, changeAfter.params??{})
                });
            }
        }

        if ($select.data('selectpicker')) return;

        $select.selectpicker();
    });

    $scope.find('.form-floating:has(select.selectpicker)').addClass('form-floating-bootstrap-select');
}

function syncSelectpickerDisabled(node) {
    if (!node) return;

    /*
     * selectpicker UI의 disabled 상태만 동기화한다.
     * option dataset은 다시 구성하지 않는다.
     */
    const $node = $(node);
    const instance = $node.data('selectpicker');

    if (!instance) return;

    instance.checkDisabled();
}

function rebuildSelectpicker(node) {
    if (!node) return;

    const $node = $(node);
    const instance = $node.data('selectpicker');

    /*
     * 기존 selectpicker 인스턴스와 내부 option 데이터를 제거한다.
     */
    if (instance) {
        $node.selectpicker('destroy');
    }

    /*
     * destroy()가 selectpicker 클래스를 제거하므로 복원한다.
     */
    $node.addClass('selectpicker');

    /*
     * changeAfter 이벤트를 다시 연결하고 selectpicker를 초기화한다.
     */
    initSelectpicker(node);
}

function bindSelectValue(node, value, mode = 'change') {
    if (!node) return;

    const $node = $(node);
    const normalizedValue = Array.isArray(value)
        ? value.map(v => String(v))
        : value == null
            ? ''
            : String(value);

    node.dataset.originalValue = Array.isArray(normalizedValue)
        ? normalizedValue.join(',')
        : normalizedValue;

    // selectpicker
    if ($node.hasClass('selectpicker')) {
        if ($node.data('selectpicker')) {
            /*
             * 원본 select, selectpicker 내부 상태,
             * 표시 UI를 함께 동기화한다.
             *
             * changed.bs.select도 플러그인 내부에서 발생한다.
             */
            $node.selectpicker('val', normalizedValue);
            return;
        }

        /*
         * 아직 selectpicker가 초기화되지 않았다면
         * 원본 select에만 값을 적용한다.
         */
        $node.val(normalizedValue);

        if (mode === 'change') {
            $node.trigger('change');
        }

        return;
    }

    $node.val(normalizedValue);

    // select2
    if ($node.hasClass('select2') || $node.hasClass('select2-repeater')) {
        switch (mode) {
            case 'none' :
                return;
            case 'handler' :
                $node.trigger('change.formSelect2');
                return;
            case 'change':
            default:
                $node.trigger('change');
        }
        return;
    }

    // normal select
    if(mode === 'change') $node.trigger('change');
}

function bindSelectpickerKeyEvents() {
    $(document)
        .off('keydown.selectpickerMenu')
        .on('keydown.selectpickerMenu', '.bootstrap-select .dropdown-menu', function (e) {
            if (e.key !== 'Enter') return;

            const $wrap = $(this).closest('.bootstrap-select');
            const $select = $wrap.find('select');
            const $toggle = $wrap.find('> button.dropdown-toggle');

            if ($select.prop('multiple')) return;

            setTimeout(function () {
                if ($toggle.hasClass('show') || $toggle.hasClass('open')) {
                    $toggle.trigger('click');
                }
            }, 0);
        });

    $(document)
        .off('keydown.selectpickerToggle')
        .on('keydown.selectpickerToggle', '.bootstrap-select > button.dropdown-toggle', function (e) {
            if (e.key !== 'ArrowDown') return;

            e.preventDefault();

            const $toggle = $(this);
            const $wrap = $toggle.closest('.bootstrap-select');
            const $select = $wrap.find('select');

            $select.selectpicker('toggle');

            if (!$toggle.hasClass('show') && !$toggle.hasClass('open')) {
                $toggle.trigger('click');
            }
        });
}

function focusSelect2(node) {
    $(node).on('select2:open', function (e) {
        $(e.target.closest('.form-floating')).addClass('select2-focus');
    });
    $(node).on('select2:close', function (e) {
        $(e.target.closest('.form-floating')).removeClass('select2-focus');
    });
    node.closest('.form-floating').hasClass('form-floating')
    && node.closest('.form-floating').addClass('form-floating-select2');
}

function onChangeTrigger(node, params)
{
    const form = node.closest('form');
    const $target = $(form).find(`[data-form-field="${params.target}"]`);
    $target.val(null).trigger('change');
}

function onChangeTriggerSelect(node, params)
{
    const form = node.closest('form');
    const targetId = form.querySelector(`[data-form-field="${params.target}"]`).id;

    params.target = `#${targetId}`;

    setDynamicSelectOptions(`#${node.id}`, params);
}

function onChangeTriggerAPIParams(node, params) {
    common.API_PARAMS[params.target] = node.value;
}
