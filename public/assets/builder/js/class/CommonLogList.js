class CommonLogList {
    constructor(options = {}) {
        const defaultApiUri = typeof common !== 'undefined' ? common.API_URI : '';
        const defaultApiParams = typeof common !== 'undefined' ? common.API_PARAMS : {};
        const defaultLocale = typeof common !== 'undefined' ? common.LOCALE : null;
        const defaultApiHeaders = typeof common !== 'undefined' ? {
            'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
        } : {};

        this.options = {
            root: document,

            apiUri: defaultApiUri,
            apiParams: defaultApiParams,
            apiHeaders: defaultApiHeaders,

            filterSelector: '#formFilter',

            listSelector: '#log-list',
            noResultSelector: '#no-result',
            noResultMessageSelector: '#no-result-message',
            templateSelector: '#log-item',
            spinnerSelector: '#log-spinner',

            listInfoSelector: '#log-list-info',
            paginationSelector: '.pagination',

            playSelector: '#btn-play',
            stopSelector: '#btn-stop',
            refreshSelector: '#btn-refresh',
            pageLengthSelector: '#log-page-length',

            draw: 0,
            pageNo: 0,
            limit: 10,
            pollingInterval: 5000,
            autoLoad: true,

            noLogsMessage: 'No logs found',
            noMatchedLogsMessage: 'No logs match the current filters',

            locale: defaultLocale,

            classFields: ['flag', 'display'],
            rawFields: [],

            normalizeItem: function (item) {
                return item;
            },

            onBeforeFetch: null,
            onAfterFetch: null,
            onError: null,

            ...options,
        };

        this.$root = $(this.options.root);

        this.pageNo = Number(this.options.pageNo ?? 0);
        this.limit = Number(this.options.limit ?? 10);
        this.pollingTimer = null;

        this.namespace = `.commonLogList_${Date.now()}_${Math.random().toString(36).slice(2)}`;

        this.find(this.options.pageLengthSelector).val(this.limit);
    }

    init() {
        this.bindFilter();
        this.bindEvents();

        if (this.options.autoLoad) {
            this.fetch();
        }

        return this;
    }

    find(selector) {
        return this.$root.find(selector);
    }

    bindFilter() {
        if (
            this.options.filterSelector &&
            $(this.options.filterSelector).length &&
            typeof activateFilterForm === 'function'
        ) {
            activateFilterForm(this.options.filterSelector, () => {
                this.pageNo = 0;
                return this.fetch();
            });
        }
    }

    bindEvents() {
        const o = this.options;

        this.$root
            .off(`click${this.namespace}`, `${o.paginationSelector} .page-link[data-page]`)
            .on(`click${this.namespace}`, `${o.paginationSelector} .page-link[data-page]`, (e) => {
                const $link = $(e.currentTarget);
                const $item = $link.closest('.page-item');

                if ($item.hasClass('disabled')) return;
                if ($item.hasClass('active')) return;

                const nextPageNo = Number($link.data('page'));

                if (Number.isNaN(nextPageNo)) return;

                this.pageNo = nextPageNo;
                this.fetch();
            });

        this.$root
            .off(`click${this.namespace}`, o.playSelector)
            .on(`click${this.namespace}`, o.playSelector, (e) => {
                $(e.currentTarget).addClass('d-none');
                this.find(o.stopSelector).removeClass('d-none');

                this.startPolling();
            });

        this.$root
            .off(`click${this.namespace}`, o.stopSelector)
            .on(`click${this.namespace}`, o.stopSelector, (e) => {
                $(e.currentTarget).addClass('d-none');
                this.find(o.playSelector).removeClass('d-none');

                this.stopPolling();
            });

        this.$root
            .off(`click${this.namespace}`, o.refreshSelector)
            .on(`click${this.namespace}`, o.refreshSelector, (e) => {
                this.fetch();
            });

        this.$root
            .off(`change${this.namespace}`, o.pageLengthSelector)
            .on(`change${this.namespace}`, o.pageLengthSelector, (e) => {
                this.limit = Number(e.currentTarget.value);
                this.pageNo = 0;

                this.fetch();
            });
    }

    getFilters() {
        if (
            this.options.filterSelector &&
            $(this.options.filterSelector).length &&
            typeof getFilterData === 'function'
        ) {
            return getFilterData(this.options.filterSelector);
        }

        return {};
    }

    getRequestData() {
        return {
            ...this.options.apiParams,
            format: 'log',
            draw: ++this.options.draw,
            pageNo: this.pageNo,
            limit: this.limit,
            filters: this.getFilters(),
        };
    }

    fetch() {
        this.showSpinner();

        if (typeof this.options.onBeforeFetch === 'function') {
            this.options.onBeforeFetch(this);
        }

        return executeAjax({
            url: this.options.apiUri,
            data: this.getRequestData(),
            headers: this.options.apiHeaders,
            success: (response) => {
                const data = response?.data ?? [];
                const total = Number(response.recordsFiltered ?? data.length);

                this.setList(data);
                this.setListInfo(total);

                if (typeof this.options.onAfterFetch === 'function') {
                    this.options.onAfterFetch(response, data, this);
                }

                this.hideSpinner();
            },
            error: (xhr) => {
                if (typeof this.options.onError === 'function') {
                    this.options.onError(xhr, this);
                }
            },
        });
    }

    setList(data) {
        const $list = this.find(this.options.listSelector);
        const $noResult = this.find(this.options.noResultSelector);

        $list.empty();

        if (Array.isArray(data) && data.length > 0) {
            $list.removeClass('d-none');
            $noResult.addClass('d-none');

            const html = data
                .map((item) => this.renderItem(item))
                .join('');

            $list.html(html);
            return;
        }

        this.emptyList();
    }

    renderItem(item) {
        const template = $(this.options.templateSelector).html() || '';
        const normalizedItem = this.options.normalizeItem(item, this) || {};

        return template.replace(/\{([a-zA-Z0-9_]+)\}/g, (matched, key) => {
            const value = normalizedItem[key] ?? '';

            if (this.options.rawFields.includes(key)) {
                return String(value);
            }

            if (this.options.classFields.includes(key)) {
                return this.escapeClassList(value);
            }

            return this.escapeHtml(value);
        });
    }

    showSpinner() {
        this.find(this.options.spinnerSelector).removeClass('d-none');
    }

    hideSpinner() {
        this.find(this.options.spinnerSelector).addClass('d-none');
    }

    emptyList() {
        this.find(this.options.listSelector).addClass('d-none');
        this.find(this.options.noResultSelector).removeClass('d-none');

        const filters = this.getFilters();
        const conditioned = this.hasCondition(filters);

        const message = conditioned
            ? this.getLocale(this.options.noMatchedLogsMessage)
            : this.getLocale(this.options.noLogsMessage);

        this.find(this.options.noResultMessageSelector).text(message);
    }

    setListInfo(total) {
        total = Number(total ?? 0);

        if (total <= 0) {
            this.find(this.options.listInfoSelector).text('Showing 0 to 0 of 0 logs');
            this.setPagination(total);
            return;
        }

        const offset = this.pageNo * this.limit + 1;
        const limit = Math.min(offset + this.limit - 1, total);

        this.find(this.options.listInfoSelector).text(
            `Showing ${offset} to ${limit} of ${total} logs`
        );

        this.setPagination(total);
    }

    setPagination(total) {
        const $pagination = this.find(this.options.paginationSelector);

        $pagination.empty();

        total = Number(total ?? 0);

        if (total <= 0) return;

        const totalPages = Math.ceil(total / this.limit);
        const currentPage = this.pageNo + 1;

        if (totalPages <= 1) return;

        const pages = this.getPaginationPages(currentPage, totalPages);

        let html = '';

        html += this.createPageItem({
            page: 0,
            type: 'first',
            icon: 'ri-skip-back-mini-line',
            disabled: this.pageNo === 0,
        });

        html += this.createPageItem({
            page: Math.max(this.pageNo - 1, 0),
            type: 'prev',
            icon: 'ri-arrow-left-s-line',
            disabled: this.pageNo === 0,
        });

        pages.forEach((page) => {
            if (page === 'ellipsis') {
                html += this.createPageItem({
                    disabled: true,
                    ellipsis: true,
                });
                return;
            }

            html += this.createPageItem({
                page: page - 1,
                label: page,
                active: page === currentPage,
            });
        });

        html += this.createPageItem({
            page: Math.min(this.pageNo + 1, totalPages - 1),
            type: 'next',
            icon: 'ri-arrow-right-s-line',
            disabled: this.pageNo >= totalPages - 1,
        });

        html += this.createPageItem({
            page: totalPages - 1,
            type: 'last',
            icon: 'ri-skip-forward-mini-line',
            disabled: this.pageNo >= totalPages - 1,
        });

        $pagination.html(html);
    }

    createPageItem({
                       page = null,
                       label = '',
                       icon = '',
                       type = '',
                       active = false,
                       disabled = false,
                       ellipsis = false,
                   }) {
        const classes = [
            'page-item',
            type,
            active ? 'active' : '',
            disabled ? 'disabled' : '',
        ].filter(Boolean).join(' ');

        if (ellipsis) {
            return `
                <li class="${classes}">
                    <a class="page-link">…</a>
                </li>
            `;
        }

        const dataAttr = page !== null ? `data-page="${page}"` : '';

        const linkClasses = [
            'page-link',
            'waves-effect',
            active ? 'waves-light' : '',
        ].filter(Boolean).join(' ');

        const content = icon
            ? `<i class="icon-base ri ${icon} icon-22px"></i>`
            : label;

        return `
            <li class="${classes}">
                <a class="${linkClasses}" href="javascript:void(0);" ${dataAttr}>${content}</a>
            </li>
        `;
    }

    getPaginationPages(currentPage, totalPages) {
        const pages = [];

        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) {
                pages.push(i);
            }

            return pages;
        }

        pages.push(1);

        const start = Math.max(2, currentPage - 1);
        const end = Math.min(totalPages - 1, currentPage + 1);

        if (start > 2) {
            pages.push('ellipsis');
        }

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        if (end < totalPages - 1) {
            pages.push('ellipsis');
        }

        pages.push(totalPages);

        return pages;
    }

    startPolling() {
        if (this.pollingTimer) return;

        this.fetch();

        this.pollingTimer = setInterval(() => {
            this.fetch();
        }, this.options.pollingInterval);
    }

    stopPolling() {
        if (!this.pollingTimer) return;

        clearInterval(this.pollingTimer);
        this.pollingTimer = null;
    }

    reload(resetPage = false) {
        if (resetPage) {
            this.pageNo = 0;
        }

        return this.fetch();
    }

    setUnit(unit) {
        this.limit = Number(unit);
        this.pageNo = 0;

        return this.fetch();
    }

    destroy() {
        this.stopPolling();
        this.$root.off(this.namespace);
    }

    hasCondition(filters) {
        if (!filters || typeof filters !== 'object') return false;

        if (filters.date) {
            const hasDate = Object.values(filters.date).some((value) => !this.isEmpty(value));
            if (hasDate) return true;
        }

        if (filters.where) {
            const hasWhere = Object.values(filters.where).some((value) => !this.isEmpty(value));
            if (hasWhere) return true;
        }

        if (Array.isArray(filters.like)) {
            const hasLike = filters.like.some((row) => {
                return !this.isEmpty(row.field) && !this.isEmpty(row.value);
            });

            if (hasLike) return true;
        }

        if (filters.text) {
            const hasText = Object.values(filters.text).some((value) => !this.isEmpty(value));
            if (hasText) return true;
        }

        return false;
    }

    isEmpty(value) {
        if (Array.isArray(value)) {
            return value.length === 0;
        }

        return value === null ||
            value === undefined ||
            String(value).trim() === '';
    }

    escapeRegExp(value) {
        return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    escapeClassList(value) {
        return String(value ?? '')
            .split(/\s+/)
            .map((token) => token.replace(/[^a-zA-Z0-9_-]/g, ''))
            .filter(Boolean)
            .join(' ');
    }

    getLocale(message) {
        if (typeof getLocale === 'function') {
            return getLocale(message, this.options.locale);
        }

        return message;
    }
}
