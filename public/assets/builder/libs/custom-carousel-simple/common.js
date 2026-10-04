(function (window, document, $) {
    'use strict';

    function CustomCarouselSimple(selector,options) {
        if (!(this instanceof CustomCarouselSimple)) {
            return new CustomCarouselSimple(selector, options);
        }

        this.selector = selector;
        this.options = Object.assign({
            maxCount: 999
        }, options || {});
        this.container = null; // 전체 wrapper
        this.list = null;      // 실제 li가 추가되는 ul
        this.sortable = null;
        this.clickHandler = null;
        this.initClass = 'custom-carousel-init';
        this.listSelector = '.custom-carousel-list';
        this.itemSelector = '.custom-carousel-item'
        this.noItemSelector = '.custom-carousel-no-items';
        this.btnDeleteSelector = '.btn-delete-carousel-item';

        this.init();
    }

    CustomCarouselSimple.prototype.init = function() {
        if(!this.isValid()) {
            console.warn('CustomCarouselSimple : selector is not valid.');
            return;
        }

        if(this.isConverted()) {
            return;
        }

        this.convert();
        this.initSortable();
        this.bindEvents();

        if (this.container) {
            this.container.__customCarouselSimple = this;
        }
    };

    CustomCarouselSimple.prototype.isValid = function() {
        if (window.Sortable === undefined) {
            console.warn('CustomCarouselSimple : Init failed. SortableJS is not called');
            return false;
        }

        const container = document.querySelector(this.selector);

        if (!container) {
            console.warn('CustomCarouselSimple : container selector is not valid.', this.selector);
            return false;
        }

        const list = container.querySelector(this.listSelector);

        if (!list) {
            console.warn(`CustomCarouselSimple : list node is not found.`, this.listSelector);
            return false;
        }

        this.container = container;
        this.list = list;

        return true;
    };

    CustomCarouselSimple.prototype.isConverted = function () {
        return this.container.classList.contains(this.initClass);
    };

    CustomCarouselSimple.prototype.convert = function () {
        this.container.classList.add('custom-carousel-init');
    };

    CustomCarouselSimple.prototype.initSortable = function () {
        const self = this;
        this.sortable = new Sortable(this.list, {
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            ghostClass: 'ghost',
            chosenClass: 'chosen',
            dragClass: 'drag',
            handle: '.custom-carousel-handle',
            onChoose: function(evt) {
                const item = evt.item;
                item.classList.add('border-primary','border-2')
            },
            onStart: function(evt) {},
            onEnd: function () {
                self.updateOrder();
            },
            onRemove: function (evt) {},
            onChange: function (evt) {},
        });
    };

    CustomCarouselSimple.prototype.bindEvents = function () {
        this.clickHandler = (e) => {
            const btn = e.target.closest(this.btnDeleteSelector);

            if (!btn) return;

            const item = btn.closest(this.itemSelector);

            if (!item || !this.list.contains(item)) return;

            item.remove();
            this.updateOrder();
        };

        this.container.addEventListener('click', this.clickHandler);
    };

    CustomCarouselSimple.prototype.clear = function () {
        this.list
            .querySelectorAll(this.itemSelector)
            .forEach((item) => item.remove());

        this.updateOrder();
    };

    CustomCarouselSimple.prototype.setData = function (items) {
        if (!Array.isArray(items)) {
            items = [];
        }

        this.clear();

        items.forEach((item) => {
            const li = this.createItem({
                carousel_id: item.carousel_id || '',
                carousel_pos: item.carousel_pos || this.list.dataset.carouselPos,
                imageUrl: item.carousel_img_url || item.carousel_img || '',
                linkTarget: item.link_target || '',
                linkUrl: item.link_url || '',
            });

            this.insertItem(li);
        });

        this.updateOrder();
    };

    CustomCarouselSimple.prototype.createItem = function (data) {
        const list = this.list;

        const field = list.dataset.carouselField;
        const position = data.carousel_pos || list.dataset.carouselPos;

        const currentItems = list.querySelectorAll(this.itemSelector).length;
        const nextIdx = currentItems + 1;
        const nameIdx = currentItems;

        const li = document.createElement('li');
        li.className = this.itemSelector.substring(1);

        const targetBlankChecked = data.linkTarget === '_blank' ? 'checked' : '';
        const targetSelfChecked = !data.linkTarget || data.linkTarget === '_self' ? 'checked' : '';

        li.innerHTML = `
            <input type="hidden" name="${field}[${nameIdx}][carousel_id]" value="${data.carousel_id || ''}">
            <input type="hidden" name="${field}[${nameIdx}][carousel_srt]" value="${nextIdx}">
            <input type="hidden" name="${field}[${nameIdx}][carousel_pos]" value="${position}">
            <input type="file" name="${field}[${nameIdx}][carousel_img]" class="d-none">
            
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex flex-start align-items-center w-75">
                    <span class="p-2 me-2 cursor-move custom-carousel-handle">
                        <i class="ri-draggable ri-18px"></i>
                    </span>
        
                    <div class="custom-carousel-item-img rounded-2 border border-input"
                         style="background-image:url(${data.imageUrl || ''})">
                    </div>
                </div>
        
                <button 
                    type="button"
                    class="btn btn-danger rounded-circle p-1 me-1 btn-delete-carousel-item"
                >
                    <i class="icon-base ri ri-close-fill icon-sm"></i>
                </button>
            </div>
            
            <div class="ps-10 mt-2 d-flex align-items-center justify-content-start">
                <input type="text" name="${field}[${nameIdx}][link_url]" class="form-control w-50 py-2 px-3 fs-small" placeholder="링크를 입력하세요" value="${data.linkUrl || ''}" data-detect-changed="false">
                
                <div class="d-flex align-items-xxl-center flex-column flex-xxl-row ms-4">
                    <div class="form-check me-xxl-2 mb-0 small">
                        <input name="${field}[${nameIdx}][link_target]" class="form-check-input" type="radio" value="_blank" id="${field}-link-target-${nameIdx}" ${targetBlankChecked}>
                        <label class="form-check-label" for="${field}-link-target-${nameIdx}"> 새창 </label>
                    </div>
                    <div class="form-check mb-0 small">
                        <input name="${field}[${nameIdx}][link_target]" class="form-check-input" type="radio" value="_self" id="${field}-link-target-${nameIdx}" ${targetSelfChecked}>
                        <label class="form-check-label" for="${field}-link-target-${nameIdx}"> 현재창 </label>
                    </div>
                </div>
            </div>
        `;

        return li;
    };

    CustomCarouselSimple.prototype.addItem = function (imageUrl, file = null) {
        const li = this.createItem({
            carousel_id: '',
            carousel_pos: this.list.dataset.carouselPos,
            imageUrl: imageUrl
        });

        this.insertItem(li);

        if (file) {
            this.setFile(
                li.querySelector('input[type=file]'),
                file
            );
        }

        this.updateOrder();
    };

    CustomCarouselSimple.prototype.insertItem = function (li) {
        const noItemEl = this.list.querySelector(this.noItemSelector);

        if (noItemEl && noItemEl.parentNode === this.list) {
            this.list.insertBefore(li, noItemEl);
        } else {
            this.list.appendChild(li);
        }
    };

    CustomCarouselSimple.prototype.updateOrder = function () {
        const fieldName =
            this.list.dataset.carouselField;

        const items =
            this.list.querySelectorAll(this.itemSelector);

        const regExp = new RegExp(
            '^(' +
            this.escapeRegExp(fieldName) +
            ')\\[\\d+\\](\\[.+\\])$'
        );

        items.forEach((item, index) => {
            item.dataset.carouselIdx = (index + 1).toString();

            item.querySelectorAll('input').forEach((input) => {

                if (!input.name) return;

                input.name = input.name.replace(
                    regExp,
                    function(full, root, rest) {
                        return root + '[' + index + ']' + rest;
                    }
                );

                if (input.name.endsWith('[carousel_srt]')) {
                    input.value = index + 1;
                }
            });
        });
    };

    CustomCarouselSimple.prototype.setFile = function (fileInput, file) {
        try {
            const dt = new DataTransfer();

            dt.items.add(file);

            fileInput.files = dt.files;

        } catch (e) {

            console.warn(e);
        }
    };

    CustomCarouselSimple.prototype.escapeRegExp = function (str) {
        return String(str)
            .replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    };

    CustomCarouselSimple.prototype.destroy = function () {

        if (this.sortable) {
            this.sortable.destroy();
            this.sortable = null;
        }

        if (this.clickHandler) {

            this.container.removeEventListener(
                'click',
                this.clickHandler
            );

            this.clickHandler = null;
        }

        this.container.classList.remove(
            this.initClass임의
        );

        this.container = null;
        this.list = null;
    };

    CustomCarouselSimple.getInstance = function (selector) {
        const container = document.querySelector(selector);

        if (!container) return null;

        return container.__customCarouselSimple || null;
    };

    window.CustomCarouselSimple = CustomCarouselSimple;

})(window, document, jQuery);
