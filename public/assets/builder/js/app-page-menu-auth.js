function afterChangeUserKind(node) {
    common.API_PARAMS.user_cd = node.value;
}

function resetMenuAuthDB() {
    executeAjax({
        url: common.API_URI + '/' + 'reset',
        success: function(response) {
            showAlert({
                type: 'success',
                title: 'Complete',
                text: response.msg,
                callback: reload,
            });
        },
    });
}
