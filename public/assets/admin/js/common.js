$(function () {
    executeAjax({
        url: '/module/logger/visit',
        method: 'post',
        data: {
            referrer : document.referrer
        },
        success: function (response) {
            console.log('Welcome')
        }
    });
});
