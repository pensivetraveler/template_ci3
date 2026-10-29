$(function () {
    executeAjax({
        url: common.MODULE_URI + 'logger/visit',
        method: 'post',
        data: {
            referrer : document.referrer
        },
        success: function (response) {
            console.log('Welcome')
        }
    });
});
