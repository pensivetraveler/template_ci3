document.addEventListener('DOMContentLoaded', function () {
    window.errorLogList = new CommonLogList({
        root: '#view-container',

        apiUri: common.API_URI,
        apiParams: {
            ...common.API_PARAMS,
            // category: 'list',
            // log_type: 'error',
        },

        filterSelector: '#formFilter',

        listSelector: '#log-list',
        noResultSelector: '#no-result',
        noResultMessageSelector: '#no-result-message',
        templateSelector: '#log-item',

        listInfoSelector: '#log-list-info',
        paginationSelector: '.pagination',

        playSelector: '#btn-play',
        stopSelector: '#btn-stop',
        pageLengthSelector: '#log-page-length',

        limit: 10,
        pollingInterval: 5000,
    }).init();
});
