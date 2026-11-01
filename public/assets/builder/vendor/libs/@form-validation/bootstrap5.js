(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else {
		var a = factory();
		for(var i in a) (typeof exports === 'object' ? exports : root)[i] = a[i];
	}
})(self, function() {
return /******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@form-validation/core/lib/cjs/index.js":
/*!*************************************************************!*\
  !*** ./node_modules/@form-validation/core/lib/cjs/index.js ***!
  \*************************************************************/
/***/ (function(__unused_webpack_module, exports) {

{


/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
/**
 * Implement Luhn validation algorithm
 * Credit to https://gist.github.com/ShirtlessKirk/2134376
 *
 * @see http://en.wikipedia.org/wiki/Luhn
 * @param {string} value
 * @returns {boolean}
 */
function luhn(value) {
    var length = value.length;
    var prodArr = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [0, 2, 4, 6, 8, 1, 3, 5, 7, 9],
    ];
    var mul = 0;
    var sum = 0;
    while (length--) {
        sum += prodArr[mul][parseInt(value.charAt(length), 10)];
        mul = 1 - mul;
    }
    return sum % 10 === 0 && sum > 0;
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
/**
 * Implement modulus 11, 10 (ISO 7064) algorithm
 *
 * @param {string} value
 * @returns {boolean}
 */
function mod11And10(value) {
    var length = value.length;
    var check = 5;
    for (var i = 0; i < length; i++) {
        check = ((((check || 10) * 2) % 11) + parseInt(value.charAt(i), 10)) % 10;
    }
    return check === 1;
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
/**
 * Implements Mod 37, 36 (ISO 7064) algorithm
 *
 * @param {string} value
 * @param {string} [alphabet]
 * @returns {boolean}
 */
function mod37And36(value, alphabet) {
    if (alphabet === void 0) { alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'; }
    var length = value.length;
    var modulus = alphabet.length;
    var check = Math.floor(modulus / 2);
    for (var i = 0; i < length; i++) {
        check = ((((check || modulus) * 2) % (modulus + 1)) + alphabet.indexOf(value.charAt(i))) % modulus;
    }
    return check === 1;
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
function transform(input) {
    return input
        .split('')
        .map(function (c) {
        var code = c.charCodeAt(0);
        // 65, 66, ..., 90 are the char code of A, B, ..., Z
        return code >= 65 && code <= 90
            ? // Replace A, B, C, ..., Z with 10, 11, ..., 35
                code - 55
            : c;
    })
        .join('')
        .split('')
        .map(function (c) { return parseInt(c, 10); });
}
function mod97And10(input) {
    var digits = transform(input);
    var temp = 0;
    var length = digits.length;
    for (var i = 0; i < length - 1; ++i) {
        temp = ((temp + digits[i]) * 10) % 97;
    }
    temp += digits[length - 1];
    return temp % 97 === 1;
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
/**
 * Implement Verhoeff validation algorithm
 * Credit to Sergey Petushkov, 2014
 *
 * @see https://en.wikipedia.org/wiki/Verhoeff_algorithm
 * @param {string} value
 * @returns {boolean}
 */
function verhoeff(value) {
    // Multiplication table d
    var d = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
        [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
        [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
        [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
        [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
        [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
        [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
        [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
        [9, 8, 7, 6, 5, 4, 3, 2, 1, 0],
    ];
    // Permutation table p
    var p = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
        [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
        [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
        [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
        [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
        [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
        [7, 0, 4, 6, 9, 1, 3, 2, 5, 8],
    ];
    // Inverse table inv
    var invertedArray = value.reverse();
    var c = 0;
    for (var i = 0; i < invertedArray.length; i++) {
        c = d[c][p[i % 8][invertedArray[i]]];
    }
    return c === 0;
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
var index$1 = {
    luhn: luhn,
    mod11And10: mod11And10,
    mod37And36: mod37And36,
    mod97And10: mod97And10,
    verhoeff: verhoeff,
};

/******************************************************************************
Copyright (c) Microsoft Corporation.

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
PERFORMANCE OF THIS SOFTWARE.
***************************************************************************** */
/* global Reflect, Promise */


function __spreadArray(to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
/**
 * @param {HTMLElement} form The form element
 * @param {string} field The field name
 * @param {HTMLElement} element The field element
 * @param {HTMLElement[]} elements The list of elements which have the same name as `field`
 * @return {string}
 */
function getFieldValue(form, field, element, elements) {
    var type = (element.getAttribute('type') || '').toLowerCase();
    var tagName = element.tagName.toLowerCase();
    if (tagName === 'textarea') {
        return element.value;
    }
    if (tagName === 'select') {
        var select = element;
        var index = select.selectedIndex;
        return index >= 0 ? select.options.item(index).value : '';
    }
    if (tagName === 'input') {
        if ('radio' === type || 'checkbox' === type) {
            var checked = elements.filter(function (ele) { return ele.checked; }).length;
            return checked === 0 ? '' : checked + '';
        }
        else {
            return element.value;
        }
    }
    return '';
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
function emitter() {
    return {
        fns: {},
        clear: function () {
            this.fns = {};
        },
        emit: function (event) {
            var args = [];
            for (var _i = 1; _i < arguments.length; _i++) {
                args[_i - 1] = arguments[_i];
            }
            (this.fns[event] || []).map(function (handler) { return handler.apply(handler, args); });
        },
        off: function (event, func) {
            if (this.fns[event]) {
                var index = this.fns[event].indexOf(func);
                if (index >= 0) {
                    this.fns[event].splice(index, 1);
                }
            }
        },
        on: function (event, func) {
            (this.fns[event] = this.fns[event] || []).push(func);
        },
    };
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
function filter() {
    return {
        filters: {},
        add: function (name, func) {
            (this.filters[name] = this.filters[name] || []).push(func);
        },
        clear: function () {
            this.filters = {};
        },
        execute: function (name, defaultValue, args) {
            if (!this.filters[name] || !this.filters[name].length) {
                return defaultValue;
            }
            var result = defaultValue;
            var filters = this.filters[name];
            var count = filters.length;
            for (var i = 0; i < count; i++) {
                result = filters[i].apply(result, args);
            }
            return result;
        },
        remove: function (name, func) {
            if (this.filters[name]) {
                this.filters[name] = this.filters[name].filter(function (f) { return f !== func; });
            }
        },
    };
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
var Core = /** @class */ (function () {
    function Core(form, fields) {
        this.fields = {};
        this.elements = {};
        this.ee = emitter();
        this.filter = filter();
        this.plugins = {};
        // Store the result of validation for each field
        this.results = new Map();
        this.validators = {};
        this.form = form;
        this.fields = fields;
    }
    Core.prototype.on = function (event, func) {
        this.ee.on(event, func);
        return this;
    };
    Core.prototype.off = function (event, func) {
        this.ee.off(event, func);
        return this;
    };
    Core.prototype.emit = function (event) {
        var _a;
        var args = [];
        for (var _i = 1; _i < arguments.length; _i++) {
            args[_i - 1] = arguments[_i];
        }
        (_a = this.ee).emit.apply(_a, __spreadArray([event], args, false));
        return this;
    };
    Core.prototype.registerPlugin = function (name, plugin) {
        // Check if whether the plugin is registered
        if (this.plugins[name]) {
            throw new Error("The plguin ".concat(name, " is registered"));
        }
        // Install the plugin
        plugin.setCore(this);
        plugin.install();
        this.plugins[name] = plugin;
        return this;
    };
    Core.prototype.deregisterPlugin = function (name) {
        var plugin = this.plugins[name];
        if (plugin) {
            plugin.uninstall();
        }
        delete this.plugins[name];
        return this;
    };
    Core.prototype.enablePlugin = function (name) {
        var plugin = this.plugins[name];
        if (plugin) {
            plugin.enable();
        }
        return this;
    };
    Core.prototype.disablePlugin = function (name) {
        var plugin = this.plugins[name];
        if (plugin) {
            plugin.disable();
        }
        return this;
    };
    Core.prototype.isPluginEnabled = function (name) {
        var plugin = this.plugins[name];
        return plugin ? plugin.isPluginEnabled() : false;
    };
    Core.prototype.registerValidator = function (name, func) {
        if (this.validators[name]) {
            throw new Error("The validator ".concat(name, " is registered"));
        }
        this.validators[name] = func;
        return this;
    };
    /**
     * Add a filter
     *
     * @param {string} name The name of filter
     * @param {Function} func The filter function
     * @return {Core}
     */
    Core.prototype.registerFilter = function (name, func) {
        this.filter.add(name, func);
        return this;
    };
    /**
     * Remove a filter
     *
     * @param {string} name The name of filter
     * @param {Function} func The filter function
     * @return {Core}
     */
    Core.prototype.deregisterFilter = function (name, func) {
        this.filter.remove(name, func);
        return this;
    };
    /**
     * Execute a filter
     *
     * @param {string} name The name of filter
     * @param {T} defaultValue The default value returns by the filter
     * @param {array} args The filter arguments
     * @returns {T}
     */
    Core.prototype.executeFilter = function (name, defaultValue, args) {
        return this.filter.execute(name, defaultValue, args);
    };
    /**
     * Add a field
     *
     * @param {string} field The field name
     * @param {FieldOptions} options The field options. The options will be merged with the original validator rules
     * if the field is already defined
     * @return {Core}
     */
    Core.prototype.addField = function (field, options) {
        var opts = Object.assign({}, {
            selector: '',
            validators: {},
        }, options);
        // Merge the options
        this.fields[field] = this.fields[field]
            ? {
                selector: opts.selector || this.fields[field].selector,
                validators: Object.assign({}, this.fields[field].validators, opts.validators),
            }
            : opts;
        this.elements[field] = this.queryElements(field);
        this.emit('core.field.added', {
            elements: this.elements[field],
            field: field,
            options: this.fields[field],
        });
        return this;
    };
    /**
     * Remove given field by name
     *
     * @param {string} field The field name
     * @return {Core}
     */
    Core.prototype.removeField = function (field) {
        if (!this.fields[field]) {
            throw new Error("The field ".concat(field, " validators are not defined. Please ensure the field is added first"));
        }
        var elements = this.elements[field];
        var options = this.fields[field];
        delete this.elements[field];
        delete this.fields[field];
        this.emit('core.field.removed', {
            elements: elements,
            field: field,
            options: options,
        });
        return this;
    };
    /**
     * Validate all fields
     *
     * @return {Promise<string>}
     */
    Core.prototype.validate = function () {
        var _this = this;
        this.emit('core.form.validating', {
            formValidation: this,
        });
        return this.filter.execute('validate-pre', Promise.resolve(), []).then(function () {
            return Promise.all(Object.keys(_this.fields).map(function (field) { return _this.validateField(field); })).then(function (results) {
                // `results` is an array of `Valid`, `Invalid` and `NotValidated`
                switch (true) {
                    case results.indexOf('Invalid') !== -1:
                        _this.emit('core.form.invalid', {
                            formValidation: _this,
                        });
                        return Promise.resolve('Invalid');
                    case results.indexOf('NotValidated') !== -1:
                        _this.emit('core.form.notvalidated', {
                            formValidation: _this,
                        });
                        return Promise.resolve('NotValidated');
                    default:
                        _this.emit('core.form.valid', {
                            formValidation: _this,
                        });
                        return Promise.resolve('Valid');
                }
            });
        });
    };
    /**
     * Validate a particular field
     *
     * @param {string} field The field name
     * @return {Promise<string>}
     */
    Core.prototype.validateField = function (field) {
        var _this = this;
        // Stop validation process if the field is already validated
        var result = this.results.get(field);
        if (result === 'Valid' || result === 'Invalid') {
            return Promise.resolve(result);
        }
        this.emit('core.field.validating', field);
        var elements = this.elements[field];
        if (elements.length === 0) {
            this.emit('core.field.valid', field);
            return Promise.resolve('Valid');
        }
        var type = elements[0].getAttribute('type');
        if ('radio' === type || 'checkbox' === type || elements.length === 1) {
            return this.validateElement(field, elements[0]);
        }
        else {
            return Promise.all(elements.map(function (ele) { return _this.validateElement(field, ele); })).then(function (results) {
                // `results` is an array of `Valid`, `Invalid` and `NotValidated`
                switch (true) {
                    case results.indexOf('Invalid') !== -1:
                        _this.emit('core.field.invalid', field);
                        _this.results.set(field, 'Invalid');
                        return Promise.resolve('Invalid');
                    case results.indexOf('NotValidated') !== -1:
                        _this.emit('core.field.notvalidated', field);
                        _this.results.delete(field);
                        return Promise.resolve('NotValidated');
                    default:
                        _this.emit('core.field.valid', field);
                        _this.results.set(field, 'Valid');
                        return Promise.resolve('Valid');
                }
            });
        }
    };
    /**
     * Validate particular element
     *
     * @param {string} field The field name
     * @param {HTMLElement} ele The field element
     * @return {Promise<string>}
     */
    Core.prototype.validateElement = function (field, ele) {
        var _this = this;
        // Reset validation result
        this.results.delete(field);
        var elements = this.elements[field];
        var ignored = this.filter.execute('element-ignored', false, [field, ele, elements]);
        if (ignored) {
            this.emit('core.element.ignored', {
                element: ele,
                elements: elements,
                field: field,
            });
            return Promise.resolve('Ignored');
        }
        var validatorList = this.fields[field].validators;
        this.emit('core.element.validating', {
            element: ele,
            elements: elements,
            field: field,
        });
        var promises = Object.keys(validatorList).map(function (v) {
            return function () { return _this.executeValidator(field, ele, v, validatorList[v]); };
        });
        return this.waterfall(promises)
            .then(function (results) {
            // `results` is an array of `Valid` or `Invalid`
            var isValid = results.indexOf('Invalid') === -1;
            _this.emit('core.element.validated', {
                element: ele,
                elements: elements,
                field: field,
                valid: isValid,
            });
            var type = ele.getAttribute('type');
            if ('radio' === type || 'checkbox' === type || elements.length === 1) {
                _this.emit(isValid ? 'core.field.valid' : 'core.field.invalid', field);
            }
            return Promise.resolve(isValid ? 'Valid' : 'Invalid');
        })
            .catch(function (reason) {
            // reason is `NotValidated`
            _this.emit('core.element.notvalidated', {
                element: ele,
                elements: elements,
                field: field,
            });
            return Promise.resolve(reason);
        });
    };
    /**
     * Perform given validator on field
     *
     * @param {string} field The field name
     * @param {HTMLElement} ele The field element
     * @param {string} v The validator name
     * @param {ValidatorOptions} opts The validator options
     * @return {Promise<string>}
     */
    Core.prototype.executeValidator = function (field, ele, v, opts) {
        var _this = this;
        var elements = this.elements[field];
        var name = this.filter.execute('validator-name', v, [v, field]);
        opts.message = this.filter.execute('validator-message', opts.message, [this.locale, field, name]);
        // Simply pass the validator if
        // - it isn't defined yet
        // - or the associated validator isn't enabled
        if (!this.validators[name] || opts.enabled === false) {
            this.emit('core.validator.validated', {
                element: ele,
                elements: elements,
                field: field,
                result: this.normalizeResult(field, name, { valid: true }),
                validator: name,
            });
            return Promise.resolve('Valid');
        }
        var validator = this.validators[name];
        // Get the field value
        var value = this.getElementValue(field, ele, name);
        var willValidate = this.filter.execute('field-should-validate', true, [field, ele, value, v]);
        if (!willValidate) {
            this.emit('core.validator.notvalidated', {
                element: ele,
                elements: elements,
                field: field,
                validator: v,
            });
            return Promise.resolve('NotValidated');
        }
        this.emit('core.validator.validating', {
            element: ele,
            elements: elements,
            field: field,
            validator: v,
        });
        // Perform validation
        var result = validator().validate({
            element: ele,
            elements: elements,
            field: field,
            l10n: this.localization,
            options: opts,
            value: value,
        });
        // Check whether the result is a `Promise`
        var isPromise = 'function' === typeof result['then'];
        if (isPromise) {
            return result.then(function (r) {
                var data = _this.normalizeResult(field, v, r);
                _this.emit('core.validator.validated', {
                    element: ele,
                    elements: elements,
                    field: field,
                    result: data,
                    validator: v,
                });
                return data.valid ? 'Valid' : 'Invalid';
            });
        }
        else {
            var data = this.normalizeResult(field, v, result);
            this.emit('core.validator.validated', {
                element: ele,
                elements: elements,
                field: field,
                result: data,
                validator: v,
            });
            return Promise.resolve(data.valid ? 'Valid' : 'Invalid');
        }
    };
    Core.prototype.getElementValue = function (field, ele, validator) {
        var defaultValue = getFieldValue(this.form, field, ele, this.elements[field]);
        return this.filter.execute('field-value', defaultValue, [defaultValue, field, ele, validator]);
    };
    // Some getter methods
    Core.prototype.getElements = function (field) {
        return this.elements[field];
    };
    Core.prototype.getFields = function () {
        return this.fields;
    };
    Core.prototype.getFormElement = function () {
        return this.form;
    };
    Core.prototype.getLocale = function () {
        return this.locale;
    };
    Core.prototype.getPlugin = function (name) {
        return this.plugins[name];
    };
    /**
     * Update the field status
     *
     * @param {string} field The field name
     * @param {string} status The new status
     * @param {string} [validator] The validator name. If it isn't specified, all validators will be updated
     * @return {Core}
     */
    Core.prototype.updateFieldStatus = function (field, status, validator) {
        var _this = this;
        var elements = this.elements[field];
        var type = elements[0].getAttribute('type');
        var list = 'radio' === type || 'checkbox' === type ? [elements[0]] : elements;
        list.forEach(function (ele) { return _this.updateElementStatus(field, ele, status, validator); });
        if (!validator) {
            switch (status) {
                case 'NotValidated':
                    this.emit('core.field.notvalidated', field);
                    this.results.delete(field);
                    break;
                case 'Validating':
                    this.emit('core.field.validating', field);
                    this.results.delete(field);
                    break;
                case 'Valid':
                    this.emit('core.field.valid', field);
                    this.results.set(field, 'Valid');
                    break;
                case 'Invalid':
                    this.emit('core.field.invalid', field);
                    this.results.set(field, 'Invalid');
                    break;
            }
        }
        else if (status === 'Invalid') {
            // We need to mark the field as invalid because it doesn't pass the `validator`
            this.emit('core.field.invalid', field);
            this.results.set(field, 'Invalid');
        }
        return this;
    };
    /**
     * Update the element status
     *
     * @param {string} field The field name
     * @param {HTMLElement} ele The field element
     * @param {string} status The new status
     * @param {string} [validator] The validator name. If it isn't specified, all validators will be updated
     * @return {Core}
     */
    Core.prototype.updateElementStatus = function (field, ele, status, validator) {
        var _this = this;
        var elements = this.elements[field];
        var fieldValidators = this.fields[field].validators;
        var validatorArr = validator ? [validator] : Object.keys(fieldValidators);
        switch (status) {
            case 'NotValidated':
                validatorArr.forEach(function (v) {
                    return _this.emit('core.validator.notvalidated', {
                        element: ele,
                        elements: elements,
                        field: field,
                        validator: v,
                    });
                });
                this.emit('core.element.notvalidated', {
                    element: ele,
                    elements: elements,
                    field: field,
                });
                break;
            case 'Validating':
                validatorArr.forEach(function (v) {
                    return _this.emit('core.validator.validating', {
                        element: ele,
                        elements: elements,
                        field: field,
                        validator: v,
                    });
                });
                this.emit('core.element.validating', {
                    element: ele,
                    elements: elements,
                    field: field,
                });
                break;
            case 'Valid':
                validatorArr.forEach(function (v) {
                    return _this.emit('core.validator.validated', {
                        element: ele,
                        elements: elements,
                        field: field,
                        result: {
                            message: fieldValidators[v].message,
                            valid: true,
                        },
                        validator: v,
                    });
                });
                this.emit('core.element.validated', {
                    element: ele,
                    elements: elements,
                    field: field,
                    valid: true,
                });
                break;
            case 'Invalid':
                validatorArr.forEach(function (v) {
                    return _this.emit('core.validator.validated', {
                        element: ele,
                        elements: elements,
                        field: field,
                        result: {
                            message: fieldValidators[v].message,
                            valid: false,
                        },
                        validator: v,
                    });
                });
                this.emit('core.element.validated', {
                    element: ele,
                    elements: elements,
                    field: field,
                    valid: false,
                });
                break;
        }
        return this;
    };
    /**
     * Reset the form. It also clears all the messages, hide the feedback icons, etc.
     *
     * @param {boolean} reset If true, the method resets field value to empty
     * or remove `checked`, `selected` attributes
     * @return {Core}
     */
    Core.prototype.resetForm = function (reset) {
        var _this = this;
        Object.keys(this.fields).forEach(function (field) { return _this.resetField(field, reset); });
        this.emit('core.form.reset', {
            formValidation: this,
            reset: reset,
        });
        return this;
    };
    /**
     * Reset the field. It also clears all the messages, hide the feedback icons, etc.
     *
     * @param {string} field The field name
     * @param {boolean} reset If true, the method resets field value to empty
     * or remove `checked`, `selected` attributes
     * @return {Core}
     */
    Core.prototype.resetField = function (field, reset) {
        // Reset the field element value if needed
        if (reset) {
            var elements = this.elements[field];
            var type_1 = elements[0].getAttribute('type');
            elements.forEach(function (ele) {
                if ('radio' === type_1 || 'checkbox' === type_1) {
                    ele.removeAttribute('selected');
                    ele.removeAttribute('checked');
                    ele.checked = false;
                }
                else {
                    ele.setAttribute('value', '');
                    if (ele instanceof HTMLInputElement || ele instanceof HTMLTextAreaElement) {
                        ele.value = '';
                    }
                }
            });
        }
        // Mark the field as not validated yet
        this.updateFieldStatus(field, 'NotValidated');
        this.emit('core.field.reset', {
            field: field,
            reset: reset,
        });
        return this;
    };
    /**
     * Revalidate a particular field. It's useful when the field value is effected by third parties
     * (for example, attach another UI library to the field).
     * Since there isn't an automatic way for FormValidation to know when the field value is modified in those cases,
     * we need to revalidate the field manually.
     *
     * @param {string} field The field name
     * @return {Promise<string>}
     */
    Core.prototype.revalidateField = function (field) {
        if (!this.fields[field]) {
            return Promise.resolve('Ignored');
        }
        this.updateFieldStatus(field, 'NotValidated');
        return this.validateField(field);
    };
    /**
     * Disable particular validator for given field
     *
     * @param {string} field The field name
     * @param {string} validator The validator name. If it isn't specified, all validators will be disabled
     * @return {Core}
     */
    Core.prototype.disableValidator = function (field, validator) {
        if (!this.fields[field]) {
            return this;
        }
        var elements = this.elements[field];
        this.toggleValidator(false, field, validator);
        this.emit('core.validator.disabled', {
            elements: elements,
            field: field,
            formValidation: this,
            validator: validator,
        });
        return this;
    };
    /**
     * Enable particular validator for given field
     *
     * @param {string} field The field name
     * @param {string} validator The validator name. If it isn't specified, all validators will be enabled
     * @return {Core}
     */
    Core.prototype.enableValidator = function (field, validator) {
        if (!this.fields[field]) {
            return this;
        }
        var elements = this.elements[field];
        this.toggleValidator(true, field, validator);
        this.emit('core.validator.enabled', {
            elements: elements,
            field: field,
            formValidation: this,
            validator: validator,
        });
        return this;
    };
    /**
     * Update option of particular validator for given field
     *
     * @param {string} field The field name
     * @param {string} validator The validator name
     * @param {string} name The option's name
     * @param {unknown} value The option's value
     * @return {Core}
     */
    Core.prototype.updateValidatorOption = function (field, validator, name, value) {
        if (this.fields[field] && this.fields[field].validators && this.fields[field].validators[validator]) {
            this.fields[field].validators[validator][name] = value;
        }
        return this;
    };
    Core.prototype.setFieldOptions = function (field, options) {
        this.fields[field] = options;
        return this;
    };
    Core.prototype.destroy = function () {
        var _this = this;
        // Remove plugins and filters
        Object.keys(this.plugins).forEach(function (id) { return _this.plugins[id].uninstall(); });
        this.ee.clear();
        this.filter.clear();
        this.results.clear();
        this.plugins = {};
        return this;
    };
    Core.prototype.setLocale = function (locale, localization) {
        this.locale = locale;
        this.localization = localization;
        return this;
    };
    Core.prototype.waterfall = function (promises) {
        return promises.reduce(function (p, c) {
            return p.then(function (res) {
                return c().then(function (result) {
                    res.push(result);
                    return res;
                });
            });
        }, Promise.resolve([]));
    };
    Core.prototype.queryElements = function (field) {
        var selector = this.fields[field].selector
            ? // Check if the selector is an ID selector which starts with `#`
                '#' === this.fields[field].selector.charAt(0)
                    ? "[id=\"".concat(this.fields[field].selector.substring(1), "\"]")
                    : this.fields[field].selector
            : "[name=\"".concat(field.replace(/"/g, '\\"'), "\"]");
        return [].slice.call(this.form.querySelectorAll(selector));
    };
    Core.prototype.normalizeResult = function (field, validator, result) {
        var opts = this.fields[field].validators[validator];
        return Object.assign({}, result, {
            message: result.message ||
                (opts ? opts.message : '') ||
                (this.localization && this.localization[validator] && this.localization[validator]['default']
                    ? this.localization[validator]['default']
                    : '') ||
                "The field ".concat(field, " is not valid"),
        });
    };
    Core.prototype.toggleValidator = function (enabled, field, validator) {
        var _this = this;
        var validatorArr = this.fields[field].validators;
        if (validator && validatorArr && validatorArr[validator]) {
            this.fields[field].validators[validator].enabled = enabled;
        }
        else if (!validator) {
            Object.keys(validatorArr).forEach(function (v) { return (_this.fields[field].validators[v].enabled = enabled); });
        }
        return this.updateFieldStatus(field, 'NotValidated', validator);
    };
    return Core;
}());
function formValidation(form, options) {
    var opts = Object.assign({}, {
        fields: {},
        locale: 'en_US',
        plugins: {},
        init: function (_) { },
    }, options);
    var core = new Core(form, opts.fields);
    core.setLocale(opts.locale, opts.localization);
    // Register plugins
    Object.keys(opts.plugins).forEach(function (name) { return core.registerPlugin(name, opts.plugins[name]); });
    // It's the single point that users can do a particular task before adding fields
    // Some initialization tasks must be done at that point
    opts.init(core);
    // and add fields
    Object.keys(opts.fields).forEach(function (field) { return core.addField(field, opts.fields[field]); });
    return core;
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
var Plugin = /** @class */ (function () {
    function Plugin(opts) {
        this.opts = opts;
        this.isEnabled = true;
    }
    Plugin.prototype.setCore = function (core) {
        this.core = core;
        return this;
    };
    Plugin.prototype.enable = function () {
        this.isEnabled = true;
        this.onEnabled();
        return this;
    };
    Plugin.prototype.disable = function () {
        this.isEnabled = false;
        this.onDisabled();
        return this;
    };
    Plugin.prototype.isPluginEnabled = function () {
        return this.isEnabled;
    };
    Plugin.prototype.onEnabled = function () { }; // eslint-disable-line @typescript-eslint/no-empty-function
    Plugin.prototype.onDisabled = function () { }; // eslint-disable-line @typescript-eslint/no-empty-function
    Plugin.prototype.install = function () { }; // eslint-disable-line @typescript-eslint/no-empty-function
    Plugin.prototype.uninstall = function () { }; // eslint-disable-line @typescript-eslint/no-empty-function
    return Plugin;
}());

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
/**
 * Execute a callback function
 *
 * @param {Function | string} functionName Can be
 * - name of global function
 * - name of namespace function (such as A.B.C)
 * - a function
 * @param {any[]} args The callback arguments
 * @return {any}
 */
function call(functionName, args) {
    if ('function' === typeof functionName) {
        return functionName.apply(this, args);
    }
    else if ('string' === typeof functionName) {
        // Node that it doesn't support node.js based environment because we are trying to access `window`
        var name_1 = functionName;
        if ('()' === name_1.substring(name_1.length - 2)) {
            name_1 = name_1.substring(0, name_1.length - 2);
        }
        var ns = name_1.split('.');
        var func = ns.pop();
        var context_1 = window;
        for (var _i = 0, ns_1 = ns; _i < ns_1.length; _i++) {
            var t = ns_1[_i];
            context_1 = context_1[t];
        }
        return typeof context_1[func] === 'undefined' ? null : context_1[func].apply(this, args);
    }
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
var addClass = function (element, classes) {
    classes.split(' ').forEach(function (clazz) {
        if (element.classList) {
            element.classList.add(clazz);
        }
        else if (" ".concat(element.className, " ").indexOf(" ".concat(clazz, " "))) {
            element.className += " ".concat(clazz);
        }
    });
};
var removeClass = function (element, classes) {
    classes.split(' ').forEach(function (clazz) {
        element.classList
            ? element.classList.remove(clazz)
            : (element.className = element.className.replace(clazz, ''));
    });
};
var classSet = function (element, classes) {
    var adding = [];
    var removing = [];
    Object.keys(classes).forEach(function (clazz) {
        if (clazz) {
            classes[clazz] ? adding.push(clazz) : removing.push(clazz);
        }
    });
    // Always remove before adding class because there might be a class which belong to both sets.
    // For example, the element will have class `a` after calling
    //  ```
    //  classSet(element, {
    //      'a a1 a2': true,
    //      'a b1 b2': false
    //  })
    //  ```
    removing.forEach(function (clazz) { return removeClass(element, clazz); });
    adding.forEach(function (clazz) { return addClass(element, clazz); });
};

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
var matches = function (element, selector) {
    var nativeMatches = element.matches ||
        element.webkitMatchesSelector ||
        element['mozMatchesSelector'] ||
        element['msMatchesSelector'];
    if (nativeMatches) {
        return nativeMatches.call(element, selector);
    }
    // In case `matchesselector` isn't supported (such as IE10)
    // See http://caniuse.com/matchesselector
    var nodes = [].slice.call(element.parentElement.querySelectorAll(selector));
    return nodes.indexOf(element) >= 0;
};
var closest = function (element, selector) {
    var ele = element;
    while (ele) {
        if (matches(ele, selector)) {
            break;
        }
        ele = ele.parentElement;
    }
    return ele;
};

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
var generateString = function (length) {
    return Array(length)
        .fill('')
        .map(function (v) { return Math.random().toString(36).charAt(2); })
        .join('');
};
var fetch = function (url, options) {
    var toQuery = function (obj) {
        return Object.keys(obj)
            .map(function (k) { return "".concat(encodeURIComponent(k), "=").concat(encodeURIComponent(obj[k])); })
            .join('&');
    };
    return new Promise(function (resolve, reject) {
        var opts = Object.assign({}, {
            crossDomain: false,
            headers: {},
            method: 'GET',
            params: {},
        }, options);
        // Build the params for GET request
        var params = Object.keys(opts.params)
            .map(function (k) { return "".concat(encodeURIComponent(k), "=").concat(encodeURIComponent(opts.params[k])); })
            .join('&');
        var hasQuery = url.indexOf('?') > -1;
        var requestUrl = 'GET' === opts.method ? "".concat(url).concat(hasQuery ? '&' : '?').concat(params) : url;
        if (opts.crossDomain) {
            // User is making cross domain request
            var script_1 = document.createElement('script');
            // In some very fast systems, the different `Date.now()` invocations can return the same value
            // which leads to the issue where there are multiple remove validators are used, for example.
            // Appending it with a generated random string can fix the value
            var callback_1 = "___FormValidationFetch_".concat(generateString(12), "___");
            window[callback_1] = function (data) {
                delete window[callback_1];
                resolve(data);
            };
            script_1.src = "".concat(requestUrl).concat(hasQuery ? '&' : '?', "callback=").concat(callback_1);
            script_1.async = true;
            script_1.addEventListener('load', function () {
                script_1.parentNode.removeChild(script_1);
            });
            script_1.addEventListener('error', function () { return reject; });
            document.head.appendChild(script_1);
        }
        else {
            var request_1 = new XMLHttpRequest();
            request_1.open(opts.method, requestUrl);
            // Set the headers
            request_1.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            if ('POST' === opts.method) {
                request_1.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            }
            Object.keys(opts.headers).forEach(function (k) { return request_1.setRequestHeader(k, opts.headers[k]); });
            request_1.addEventListener('load', function () {
                // Cannot use arrow function here due to the `this` scope
                resolve(JSON.parse(this.responseText));
            });
            request_1.addEventListener('error', function () { return reject; });
            // GET request will ignore the passed data here
            request_1.send(toQuery(opts.params));
        }
    });
};

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
/**
 * Format a string
 * It's used to format the error message
 * format('The field must between %s and %s', [10, 20]) = 'The field must between 10 and 20'
 *
 * @param {string} message
 * @param {string|string[]} parameters
 * @returns {string}
 */
var format = function (message, parameters) {
    var params = Array.isArray(parameters) ? parameters : [parameters];
    var output = message;
    params.forEach(function (p) {
        output = output.replace('%s', p);
    });
    return output;
};

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
var hasClass = function (element, clazz) {
    return element.classList
        ? element.classList.contains(clazz)
        : new RegExp("(^| )".concat(clazz, "( |$)"), 'gi').test(element.className);
};

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
/**
 * Validate a date
 *
 * @param {string} year The full year in 4 digits
 * @param {string} month The month number
 * @param {string} day The day number
 * @param {boolean} [notInFuture] If true, the date must not be in the future
 * @returns {boolean}
 */
var isValidDate = function (year, month, day, notInFuture) {
    if (isNaN(year) || isNaN(month) || isNaN(day)) {
        return false;
    }
    if (year < 1000 || year > 9999 || month <= 0 || month > 12) {
        return false;
    }
    var numDays = [
        31,
        // Update the number of days in Feb of leap year
        year % 400 === 0 || (year % 100 !== 0 && year % 4 === 0) ? 29 : 28,
        31,
        30,
        31,
        30,
        31,
        31,
        30,
        31,
        30,
        31,
    ];
    // Check the day
    if (day <= 0 || day > numDays[month - 1]) {
        return false;
    }
    if (notInFuture === true) {
        var currentDate = new Date();
        var currentYear = currentDate.getFullYear();
        var currentMonth = currentDate.getMonth();
        var currentDay = currentDate.getDate();
        return (year < currentYear ||
            (year === currentYear && month - 1 < currentMonth) ||
            (year === currentYear && month - 1 === currentMonth && day < currentDay));
    }
    return true;
};

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
var removeUndefined = function (obj) {
    return obj
        ? Object.entries(obj).reduce(function (a, _a) {
            var k = _a[0], v = _a[1];
            return (v === undefined ? a : ((a[k] = v), a));
        }, {})
        : {};
};

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
var index = {
    call: call,
    classSet: classSet,
    closest: closest,
    fetch: fetch,
    format: format,
    hasClass: hasClass,
    isValidDate: isValidDate,
    removeUndefined: removeUndefined,
};

exports.Plugin = Plugin;
exports.algorithms = index$1;
exports.formValidation = formValidation;
exports.utils = index;
}

/***/ }),

/***/ "./node_modules/@form-validation/core/lib/index.js":
/*!*********************************************************!*\
  !*** ./node_modules/@form-validation/core/lib/index.js ***!
  \*********************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

{
/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */



if (false) {} else {
    module.exports = __webpack_require__(/*! ./cjs/index.js */ "./node_modules/@form-validation/core/lib/cjs/index.js");
}
}

/***/ }),

/***/ "./node_modules/@form-validation/plugin-bootstrap5/lib/cjs/index.js":
/*!**************************************************************************!*\
  !*** ./node_modules/@form-validation/plugin-bootstrap5/lib/cjs/index.js ***!
  \**************************************************************************/
/***/ (function(__unused_webpack_module, exports, __webpack_require__) {

{


var core = __webpack_require__(/*! @form-validation/core */ "./node_modules/@form-validation/core/lib/index.js");
var pluginFramework = __webpack_require__(/*! @form-validation/plugin-framework */ "./node_modules/@form-validation/plugin-framework/lib/index.js");

/******************************************************************************
Copyright (c) Microsoft Corporation.

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
PERFORMANCE OF THIS SOFTWARE.
***************************************************************************** */
/* global Reflect, Promise */

var extendStatics = function(d, b) {
    extendStatics = Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
        function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
    return extendStatics(d, b);
};

function __extends(d, b) {
    if (typeof b !== "function" && b !== null)
        throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
    extendStatics(d, b);
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
var classSet = core.utils.classSet, hasClass = core.utils.hasClass;
var Bootstrap5 = /** @class */ (function (_super) {
    __extends(Bootstrap5, _super);
    function Bootstrap5(opts) {
        var _this = _super.call(this, Object.assign({}, {
            eleInvalidClass: 'is-invalid',
            eleValidClass: 'is-valid',
            formClass: 'fv-plugins-bootstrap5',
            rowInvalidClass: 'fv-plugins-bootstrap5-row-invalid',
            rowPattern: /^(.*)(col|offset)(-(sm|md|lg|xl))*-[0-9]+(.*)$/,
            rowSelector: '.row',
            rowValidClass: 'fv-plugins-bootstrap5-row-valid',
        }, opts)) || this;
        _this.eleValidatedHandler = _this.handleElementValidated.bind(_this);
        return _this;
    }
    Bootstrap5.prototype.install = function () {
        _super.prototype.install.call(this);
        this.core.on('core.element.validated', this.eleValidatedHandler);
    };
    Bootstrap5.prototype.uninstall = function () {
        _super.prototype.uninstall.call(this);
        this.core.off('core.element.validated', this.eleValidatedHandler);
    };
    Bootstrap5.prototype.handleElementValidated = function (e) {
        var type = e.element.getAttribute('type');
        // If we use more than 1 inline checkbox/radio, we need to add `is-invalid` for the `form-check` container
        // so the error messages are displayed properly.
        // The markup looks like as following:
        //  <div class="form-check form-check-inline is-invalid">
        //      <input type="checkbox" class="form-check-input is-invalid" />
        //      <label class="form-check-label">...</label>
        //  <div>
        //  <!-- Other inline checkboxes/radios go here -->
        //  <!-- Then message element -->
        //  <div class="fv-plugins-message-container invalid-feedback">...</div>
        if (('checkbox' === type || 'radio' === type) &&
            e.elements.length > 1 &&
            hasClass(e.element, 'form-check-input')) {
            var inputParent = e.element.parentElement;
            if (hasClass(inputParent, 'form-check') && hasClass(inputParent, 'form-check-inline')) {
                classSet(inputParent, {
                    'is-invalid': !e.valid,
                    'is-valid': e.valid,
                });
            }
        }
    };
    Bootstrap5.prototype.onIconPlaced = function (e) {
        // Disable the default icon of Bootstrap 5
        classSet(e.element, {
            'fv-plugins-icon-input': true,
        });
        // Adjust icon place if the field belongs to a `input-group`
        var parent = e.element.parentElement;
        if (hasClass(parent, 'input-group')) {
            parent.parentElement.insertBefore(e.iconElement, parent.nextSibling);
            if (e.element.nextElementSibling && hasClass(e.element.nextElementSibling, 'input-group-text')) {
                classSet(e.iconElement, {
                    'fv-plugins-icon-input-group': true,
                });
            }
        }
        var type = e.element.getAttribute('type');
        if ('checkbox' === type || 'radio' === type) {
            var grandParent = parent.parentElement;
            // Place it after the container of checkbox/radio
            if (hasClass(parent, 'form-check')) {
                classSet(e.iconElement, {
                    'fv-plugins-icon-check': true,
                });
                parent.parentElement.insertBefore(e.iconElement, parent.nextSibling);
            }
            else if (hasClass(parent.parentElement, 'form-check')) {
                classSet(e.iconElement, {
                    'fv-plugins-icon-check': true,
                });
                grandParent.parentElement.insertBefore(e.iconElement, grandParent.nextSibling);
            }
        }
    };
    Bootstrap5.prototype.onMessagePlaced = function (e) {
        e.messageElement.classList.add('invalid-feedback');
        // Check if the input is placed inside an `input-group` element
        var inputParent = e.element.parentElement;
        if (hasClass(inputParent, 'input-group')) {
            // The markup looks like
            //  <div class="input-group">
            //      <span class="input-group-text">...</span>
            //      <input type="text" class="form-control" />
            //      <!-- We will place the error message here, at the end of parent -->
            //  </div>
            inputParent.appendChild(e.messageElement);
            // Keep the border radius of the right corners
            classSet(inputParent, {
                'has-validation': true,
            });
            return;
        }
        var type = e.element.getAttribute('type');
        if (('checkbox' === type || 'radio' === type) &&
            hasClass(e.element, 'form-check-input') &&
            hasClass(inputParent, 'form-check') &&
            !hasClass(inputParent, 'form-check-inline')) {
            // Place the message inside the `form-check` container of the last checkbox/radio
            e.elements[e.elements.length - 1].parentElement.appendChild(e.messageElement);
        }
    };
    return Bootstrap5;
}(pluginFramework.Framework));

exports.Bootstrap5 = Bootstrap5;
}

/***/ }),

/***/ "./node_modules/@form-validation/plugin-bootstrap5/lib/index.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@form-validation/plugin-bootstrap5/lib/index.js ***!
  \**********************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

{
/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */



if (false) {} else {
    module.exports = __webpack_require__(/*! ./cjs/index.js */ "./node_modules/@form-validation/plugin-bootstrap5/lib/cjs/index.js");
}
}

/***/ }),

/***/ "./node_modules/@form-validation/plugin-framework/lib/cjs/index.js":
/*!*************************************************************************!*\
  !*** ./node_modules/@form-validation/plugin-framework/lib/cjs/index.js ***!
  \*************************************************************************/
/***/ (function(__unused_webpack_module, exports, __webpack_require__) {

{


var core = __webpack_require__(/*! @form-validation/core */ "./node_modules/@form-validation/core/lib/index.js");
var pluginMessage = __webpack_require__(/*! @form-validation/plugin-message */ "./node_modules/@form-validation/plugin-message/lib/index.js");

/******************************************************************************
Copyright (c) Microsoft Corporation.

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
PERFORMANCE OF THIS SOFTWARE.
***************************************************************************** */
/* global Reflect, Promise */

var extendStatics = function(d, b) {
    extendStatics = Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
        function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
    return extendStatics(d, b);
};

function __extends(d, b) {
    if (typeof b !== "function" && b !== null)
        throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
    extendStatics(d, b);
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
var classSet = core.utils.classSet, closest = core.utils.closest;
var Framework = /** @class */ (function (_super) {
    __extends(Framework, _super);
    function Framework(opts) {
        var _this = _super.call(this, opts) || this;
        _this.results = new Map();
        _this.containers = new Map();
        _this.opts = Object.assign({}, {
            defaultMessageContainer: true,
            eleInvalidClass: '',
            eleValidClass: '',
            rowClasses: '',
            rowValidatingClass: '',
        }, opts);
        _this.elementIgnoredHandler = _this.onElementIgnored.bind(_this);
        _this.elementValidatingHandler = _this.onElementValidating.bind(_this);
        _this.elementValidatedHandler = _this.onElementValidated.bind(_this);
        _this.elementNotValidatedHandler = _this.onElementNotValidated.bind(_this);
        _this.iconPlacedHandler = _this.onIconPlaced.bind(_this);
        _this.fieldAddedHandler = _this.onFieldAdded.bind(_this);
        _this.fieldRemovedHandler = _this.onFieldRemoved.bind(_this);
        _this.messagePlacedHandler = _this.onMessagePlaced.bind(_this);
        return _this;
    }
    Framework.prototype.install = function () {
        var _a;
        var _this = this;
        classSet(this.core.getFormElement(), (_a = {},
            _a[this.opts.formClass] = true,
            _a['fv-plugins-framework'] = true,
            _a));
        this.core
            .on('core.element.ignored', this.elementIgnoredHandler)
            .on('core.element.validating', this.elementValidatingHandler)
            .on('core.element.validated', this.elementValidatedHandler)
            .on('core.element.notvalidated', this.elementNotValidatedHandler)
            .on('plugins.icon.placed', this.iconPlacedHandler)
            .on('core.field.added', this.fieldAddedHandler)
            .on('core.field.removed', this.fieldRemovedHandler);
        if (this.opts.defaultMessageContainer) {
            this.core.registerPlugin(Framework.MESSAGE_PLUGIN, new pluginMessage.Message({
                clazz: this.opts.messageClass,
                container: function (field, element) {
                    var selector = 'string' === typeof _this.opts.rowSelector
                        ? _this.opts.rowSelector
                        : _this.opts.rowSelector(field, element);
                    var groupEle = closest(element, selector);
                    return pluginMessage.Message.getClosestContainer(element, groupEle, _this.opts.rowPattern);
                },
            }));
            this.core.on('plugins.message.placed', this.messagePlacedHandler);
        }
    };
    Framework.prototype.uninstall = function () {
        var _a;
        this.results.clear();
        this.containers.clear();
        classSet(this.core.getFormElement(), (_a = {},
            _a[this.opts.formClass] = false,
            _a['fv-plugins-framework'] = false,
            _a));
        this.core
            .off('core.element.ignored', this.elementIgnoredHandler)
            .off('core.element.validating', this.elementValidatingHandler)
            .off('core.element.validated', this.elementValidatedHandler)
            .off('core.element.notvalidated', this.elementNotValidatedHandler)
            .off('plugins.icon.placed', this.iconPlacedHandler)
            .off('core.field.added', this.fieldAddedHandler)
            .off('core.field.removed', this.fieldRemovedHandler);
        if (this.opts.defaultMessageContainer) {
            this.core.deregisterPlugin(Framework.MESSAGE_PLUGIN);
            this.core.off('plugins.message.placed', this.messagePlacedHandler);
        }
    };
    Framework.prototype.onEnabled = function () {
        var _a;
        classSet(this.core.getFormElement(), (_a = {},
            _a[this.opts.formClass] = true,
            _a));
        if (this.opts.defaultMessageContainer) {
            this.core.enablePlugin(Framework.MESSAGE_PLUGIN);
        }
    };
    Framework.prototype.onDisabled = function () {
        var _a;
        classSet(this.core.getFormElement(), (_a = {},
            _a[this.opts.formClass] = false,
            _a));
        if (this.opts.defaultMessageContainer) {
            this.core.disablePlugin(Framework.MESSAGE_PLUGIN);
        }
    };
    Framework.prototype.onIconPlaced = function (_e) { }; // eslint-disable-line @typescript-eslint/no-empty-function
    Framework.prototype.onMessagePlaced = function (_e) { }; // eslint-disable-line @typescript-eslint/no-empty-function
    Framework.prototype.onFieldAdded = function (e) {
        var _this = this;
        var elements = e.elements;
        if (elements) {
            elements.forEach(function (ele) {
                var _a;
                var groupEle = _this.containers.get(ele);
                if (groupEle) {
                    classSet(groupEle, (_a = {},
                        _a[_this.opts.rowInvalidClass] = false,
                        _a[_this.opts.rowValidatingClass] = false,
                        _a[_this.opts.rowValidClass] = false,
                        _a['fv-plugins-icon-container'] = false,
                        _a));
                    _this.containers.delete(ele);
                }
            });
            this.prepareFieldContainer(e.field, elements);
        }
    };
    Framework.prototype.onFieldRemoved = function (e) {
        var _this = this;
        e.elements.forEach(function (ele) {
            var _a;
            var groupEle = _this.containers.get(ele);
            if (groupEle) {
                classSet(groupEle, (_a = {},
                    _a[_this.opts.rowInvalidClass] = false,
                    _a[_this.opts.rowValidatingClass] = false,
                    _a[_this.opts.rowValidClass] = false,
                    _a));
            }
        });
    };
    Framework.prototype.prepareFieldContainer = function (field, elements) {
        var _this = this;
        if (elements.length) {
            var type = elements[0].getAttribute('type');
            if ('radio' === type || 'checkbox' === type) {
                this.prepareElementContainer(field, elements[0]);
            }
            else {
                elements.forEach(function (ele) { return _this.prepareElementContainer(field, ele); });
            }
        }
    };
    Framework.prototype.prepareElementContainer = function (field, element) {
        var _a;
        var selector = 'string' === typeof this.opts.rowSelector ? this.opts.rowSelector : this.opts.rowSelector(field, element);
        var groupEle = closest(element, selector);
        if (groupEle !== element) {
            classSet(groupEle, (_a = {},
                _a[this.opts.rowClasses] = true,
                _a['fv-plugins-icon-container'] = true,
                _a));
            this.containers.set(element, groupEle);
        }
    };
    Framework.prototype.onElementValidating = function (e) {
        this.removeClasses(e.element, e.elements);
    };
    Framework.prototype.onElementNotValidated = function (e) {
        this.removeClasses(e.element, e.elements);
    };
    Framework.prototype.onElementIgnored = function (e) {
        this.removeClasses(e.element, e.elements);
    };
    Framework.prototype.removeClasses = function (element, elements) {
        var _a;
        var _this = this;
        var type = element.getAttribute('type');
        var ele = 'radio' === type || 'checkbox' === type ? elements[0] : element;
        elements.forEach(function (ele) {
            var _a;
            classSet(ele, (_a = {},
                _a[_this.opts.eleValidClass] = false,
                _a[_this.opts.eleInvalidClass] = false,
                _a));
        });
        var groupEle = this.containers.get(ele);
        if (groupEle) {
            classSet(groupEle, (_a = {},
                _a[this.opts.rowInvalidClass] = false,
                _a[this.opts.rowValidatingClass] = false,
                _a[this.opts.rowValidClass] = false,
                _a));
        }
    };
    Framework.prototype.onElementValidated = function (e) {
        var _a, _b;
        var _this = this;
        var elements = e.elements;
        var type = e.element.getAttribute('type');
        var element = 'radio' === type || 'checkbox' === type ? elements[0] : e.element;
        // Set the valid or invalid class for all elements
        elements.forEach(function (ele) {
            var _a;
            classSet(ele, (_a = {},
                _a[_this.opts.eleValidClass] = e.valid,
                _a[_this.opts.eleInvalidClass] = !e.valid,
                _a));
        });
        var groupEle = this.containers.get(element);
        if (groupEle) {
            if (!e.valid) {
                this.results.set(element, false);
                classSet(groupEle, (_a = {},
                    _a[this.opts.rowInvalidClass] = true,
                    _a[this.opts.rowValidatingClass] = false,
                    _a[this.opts.rowValidClass] = false,
                    _a));
            }
            else {
                this.results.delete(element);
                // Maybe there're multiple fields belong to the same row
                var isValid_1 = true;
                this.containers.forEach(function (value, key) {
                    if (value === groupEle && _this.results.get(key) === false) {
                        isValid_1 = false;
                    }
                });
                // If all field(s) belonging to the row are valid
                if (isValid_1) {
                    classSet(groupEle, (_b = {},
                        _b[this.opts.rowInvalidClass] = false,
                        _b[this.opts.rowValidatingClass] = false,
                        _b[this.opts.rowValidClass] = true,
                        _b));
                }
            }
        }
    };
    Framework.MESSAGE_PLUGIN = '___frameworkMessage';
    return Framework;
}(core.Plugin));

exports.Framework = Framework;
}

/***/ }),

/***/ "./node_modules/@form-validation/plugin-framework/lib/index.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@form-validation/plugin-framework/lib/index.js ***!
  \*********************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

{
/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */



if (false) {} else {
    module.exports = __webpack_require__(/*! ./cjs/index.js */ "./node_modules/@form-validation/plugin-framework/lib/cjs/index.js");
}
}

/***/ }),

/***/ "./node_modules/@form-validation/plugin-message/lib/cjs/index.js":
/*!***********************************************************************!*\
  !*** ./node_modules/@form-validation/plugin-message/lib/cjs/index.js ***!
  \***********************************************************************/
/***/ (function(__unused_webpack_module, exports, __webpack_require__) {

{


var core = __webpack_require__(/*! @form-validation/core */ "./node_modules/@form-validation/core/lib/index.js");

/******************************************************************************
Copyright (c) Microsoft Corporation.

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
PERFORMANCE OF THIS SOFTWARE.
***************************************************************************** */
/* global Reflect, Promise */

var extendStatics = function(d, b) {
    extendStatics = Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
        function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
    return extendStatics(d, b);
};

function __extends(d, b) {
    if (typeof b !== "function" && b !== null)
        throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
    extendStatics(d, b);
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
}

/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */
var classSet = core.utils.classSet;
var Message = /** @class */ (function (_super) {
    __extends(Message, _super);
    function Message(opts) {
        var _this = _super.call(this, opts) || this;
        _this.useDefaultContainer = false;
        // Map the field element to message container
        _this.messages = new Map();
        // By default, we will display error messages at the bottom of form
        _this.defaultContainer = document.createElement('div');
        _this.useDefaultContainer = !opts || !opts.container;
        _this.opts = Object.assign({}, {
            container: function (_field, _element) { return _this.defaultContainer; },
        }, opts);
        _this.elementIgnoredHandler = _this.onElementIgnored.bind(_this);
        _this.fieldAddedHandler = _this.onFieldAdded.bind(_this);
        _this.fieldRemovedHandler = _this.onFieldRemoved.bind(_this);
        _this.validatorValidatedHandler = _this.onValidatorValidated.bind(_this);
        _this.validatorNotValidatedHandler = _this.onValidatorNotValidated.bind(_this);
        return _this;
    }
    /**
     * Determine the closest element that its class matches with given pattern.
     * In popular cases, all the fields might follow the same markup, so that closest element
     * can be used as message container.
     *
     * For example, if we use the Bootstrap framework then the field often be placed inside a
     * `col-{size}-{numberOfColumns}` class, we can register the plugin as following:
     * ```
     *  formValidation(form, {
     *      plugins: {
     *          message: new Message({
     *              container: function(field, element) {
     *                  return Message.getClosestContainer(element, form, /^(.*)(col|offset)-(xs|sm|md|lg)-[0-9]+(.*)$/)
     *              }
     *          })
     *      }
     *  })
     * ```
     *
     * @param element The field element
     * @param upper The upper element, so we don't have to look for the entire page
     * @param pattern The pattern
     * @return {HTMLElement}
     */
    Message.getClosestContainer = function (element, upper, pattern) {
        var ele = element;
        while (ele) {
            if (ele === upper) {
                break;
            }
            ele = ele.parentElement;
            if (pattern.test(ele.className)) {
                break;
            }
        }
        return ele;
    };
    Message.prototype.install = function () {
        if (this.useDefaultContainer) {
            this.core.getFormElement().appendChild(this.defaultContainer);
        }
        this.core
            .on('core.element.ignored', this.elementIgnoredHandler)
            .on('core.field.added', this.fieldAddedHandler)
            .on('core.field.removed', this.fieldRemovedHandler)
            .on('core.validator.validated', this.validatorValidatedHandler)
            .on('core.validator.notvalidated', this.validatorNotValidatedHandler);
    };
    Message.prototype.uninstall = function () {
        if (this.useDefaultContainer) {
            this.core.getFormElement().removeChild(this.defaultContainer);
        }
        this.messages.forEach(function (message) { return message.parentNode.removeChild(message); });
        this.messages.clear();
        this.core
            .off('core.element.ignored', this.elementIgnoredHandler)
            .off('core.field.added', this.fieldAddedHandler)
            .off('core.field.removed', this.fieldRemovedHandler)
            .off('core.validator.validated', this.validatorValidatedHandler)
            .off('core.validator.notvalidated', this.validatorNotValidatedHandler);
    };
    Message.prototype.onEnabled = function () {
        this.messages.forEach(function (_element, message, _map) {
            classSet(message, {
                'fv-plugins-message-container--enabled': true,
                'fv-plugins-message-container--disabled': false,
            });
        });
    };
    Message.prototype.onDisabled = function () {
        this.messages.forEach(function (_element, message, _map) {
            classSet(message, {
                'fv-plugins-message-container--enabled': false,
                'fv-plugins-message-container--disabled': true,
            });
        });
    };
    // Prepare message container for new added field
    Message.prototype.onFieldAdded = function (e) {
        var _this = this;
        var elements = e.elements;
        if (elements) {
            elements.forEach(function (ele) {
                var msg = _this.messages.get(ele);
                if (msg) {
                    msg.parentNode.removeChild(msg);
                    _this.messages.delete(ele);
                }
            });
            this.prepareFieldContainer(e.field, elements);
        }
    };
    // When a field is removed, we remove all error messages that associates with the field
    Message.prototype.onFieldRemoved = function (e) {
        var _this = this;
        if (!e.elements.length || !e.field) {
            return;
        }
        var type = e.elements[0].getAttribute('type');
        var elements = 'radio' === type || 'checkbox' === type ? [e.elements[0]] : e.elements;
        elements.forEach(function (ele) {
            if (_this.messages.has(ele)) {
                var container = _this.messages.get(ele);
                container.parentNode.removeChild(container);
                _this.messages.delete(ele);
            }
        });
    };
    Message.prototype.prepareFieldContainer = function (field, elements) {
        var _this = this;
        if (elements.length) {
            var type = elements[0].getAttribute('type');
            if ('radio' === type || 'checkbox' === type) {
                this.prepareElementContainer(field, elements[0], elements);
            }
            else {
                elements.forEach(function (ele) { return _this.prepareElementContainer(field, ele, elements); });
            }
        }
    };
    Message.prototype.prepareElementContainer = function (field, element, elements) {
        var container;
        if ('string' === typeof this.opts.container) {
            var selector = '#' === this.opts.container.charAt(0)
                ? "[id=\"".concat(this.opts.container.substring(1), "\"]")
                : this.opts.container;
            container = this.core.getFormElement().querySelector(selector);
        }
        else {
            container = this.opts.container(field, element);
        }
        var message = document.createElement('div');
        container.appendChild(message);
        classSet(message, {
            'fv-plugins-message-container': true,
            'fv-plugins-message-container--enabled': this.isEnabled,
            'fv-plugins-message-container--disabled': !this.isEnabled,
        });
        this.core.emit('plugins.message.placed', {
            element: element,
            elements: elements,
            field: field,
            messageElement: message,
        });
        this.messages.set(element, message);
    };
    Message.prototype.getMessage = function (result) {
        return typeof result.message === 'string' ? result.message : result.message[this.core.getLocale()];
    };
    Message.prototype.onValidatorValidated = function (e) {
        var _a;
        var elements = e.elements;
        var type = e.element.getAttribute('type');
        var element = ('radio' === type || 'checkbox' === type) && elements.length > 0 ? elements[0] : e.element;
        if (this.messages.has(element)) {
            var container = this.messages.get(element);
            var messageEle = container.querySelector("[data-field=\"".concat(e.field.replace(/"/g, '\\"'), "\"][data-validator=\"").concat(e.validator.replace(/"/g, '\\"'), "\"]"));
            if (!messageEle && !e.result.valid) {
                var ele = document.createElement('div');
                ele.innerHTML = this.getMessage(e.result);
                ele.setAttribute('data-field', e.field);
                ele.setAttribute('data-validator', e.validator);
                if (this.opts.clazz) {
                    classSet(ele, (_a = {},
                        _a[this.opts.clazz] = true,
                        _a));
                }
                container.appendChild(ele);
                this.core.emit('plugins.message.displayed', {
                    element: e.element,
                    field: e.field,
                    message: e.result.message,
                    messageElement: ele,
                    meta: e.result.meta,
                    validator: e.validator,
                });
            }
            else if (messageEle && !e.result.valid) {
                // The validator returns new message
                messageEle.innerHTML = this.getMessage(e.result);
                this.core.emit('plugins.message.displayed', {
                    element: e.element,
                    field: e.field,
                    message: e.result.message,
                    messageElement: messageEle,
                    meta: e.result.meta,
                    validator: e.validator,
                });
            }
            else if (messageEle && e.result.valid) {
                // Field is valid
                container.removeChild(messageEle);
            }
        }
    };
    Message.prototype.onValidatorNotValidated = function (e) {
        var elements = e.elements;
        var type = e.element.getAttribute('type');
        var element = 'radio' === type || 'checkbox' === type ? elements[0] : e.element;
        if (this.messages.has(element)) {
            var container = this.messages.get(element);
            var messageEle = container.querySelector("[data-field=\"".concat(e.field.replace(/"/g, '\\"'), "\"][data-validator=\"").concat(e.validator.replace(/"/g, '\\"'), "\"]"));
            if (messageEle) {
                container.removeChild(messageEle);
            }
        }
    };
    Message.prototype.onElementIgnored = function (e) {
        var elements = e.elements;
        var type = e.element.getAttribute('type');
        var element = 'radio' === type || 'checkbox' === type ? elements[0] : e.element;
        if (this.messages.has(element)) {
            var container_1 = this.messages.get(element);
            var messageElements = [].slice.call(container_1.querySelectorAll("[data-field=\"".concat(e.field.replace(/"/g, '\\"'), "\"]")));
            messageElements.forEach(function (messageEle) {
                container_1.removeChild(messageEle);
            });
        }
    };
    return Message;
}(core.Plugin));

exports.Message = Message;
}

/***/ }),

/***/ "./node_modules/@form-validation/plugin-message/lib/index.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@form-validation/plugin-message/lib/index.js ***!
  \*******************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

{
/**
 * FormValidation (https://formvalidation.io)
 * The best validation library for JavaScript
 * (c) 2013 - 2023 Nguyen Huu Phuoc <me@phuoc.ng>
 */



if (false) {} else {
    module.exports = __webpack_require__(/*! ./cjs/index.js */ "./node_modules/@form-validation/plugin-message/lib/cjs/index.js");
}
}

/***/ }),

/***/ "./libs/@form-validation/bootstrap5.js":
/*!*********************************************!*\
  !*** ./libs/@form-validation/bootstrap5.js ***!
  \*********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

{
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Bootstrap5: function() { return /* reexport safe */ _form_validation_plugin_bootstrap5__WEBPACK_IMPORTED_MODULE_0__.Bootstrap5; }
/* harmony export */ });
/* harmony import */ var _form_validation_plugin_bootstrap5__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @form-validation/plugin-bootstrap5 */ "./node_modules/@form-validation/plugin-bootstrap5/lib/index.js");

try {
  FormValidation.plugins.Bootstrap5 = _form_validation_plugin_bootstrap5__WEBPACK_IMPORTED_MODULE_0__.Bootstrap5;
} catch (e) {}
}

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./libs/@form-validation/bootstrap5.js");
/******/ 	
/******/ 	return __webpack_exports__;
/******/ })()
;
});