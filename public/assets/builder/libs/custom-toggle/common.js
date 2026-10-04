(function (window, document, $) {
    'use strict';

    function CustomToggle(element, options) {
        this.$element = $(element);
        this.element = this.$element[0];

        this.options = $.extend({
            wrapperClass: 'custom-toggle-wrapper',
            labelClass: 'custom-toggle-label',
            buttonClass: 'custom-toggle-btn'
        }, options || {});

        this.init();
    }

    CustomToggle.prototype.init = function () {
        if (!this.isValid()) {
            return;
        }

        if (this.isConverted()) {
            return;
        }

        this.convert();
    };

    CustomToggle.prototype.isValid = function () {
        return this.$element.is('input[type="checkbox"]') && this.$element.is('[data-custom-toggle]');
    };

    CustomToggle.prototype.isConverted = function () {
        return this.$element.parent().hasClass(this.options.wrapperClass);
    };

    CustomToggle.prototype.convert = function () {
        var id = this.$element.attr('id');

        if (!id) {
            id = 'custom_toggle_' + Math.random().toString(36).substring(2, 10);
            this.$element.attr('id', id);
        }

        // 변환 후에는 data-custom-toggle 제거
        this.$element.removeAttr('data-custom-toggle');

        var $wrapper = $('<div>', {
            class: this.options.wrapperClass
        });

        var $label = $('<label>', {
            for: id,
            class: this.options.labelClass
        });

        var $button = $('<span>', {
            class: this.options.buttonClass
        });

        $label.append($button);

        // 기존 input 위치에 wrapper 삽입
        this.$element.before($wrapper);

        // input을 wrapper 내부로 이동
        $wrapper.append(this.$element);
        $wrapper.append($label);
    };

    window.CustomToggle = CustomToggle;

})(window, document, jQuery);
