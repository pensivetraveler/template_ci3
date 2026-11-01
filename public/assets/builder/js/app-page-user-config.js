$(function() {
    const form = document.getElementById('formUserCfg');

    form.addEventListener('submit', function() {
        const mode = form.querySelector('input[name="password_find_mode"]:checked');

        executeAjax({
            url: common.API_URI + '/save',
            method: 'post',
            data: {
                signup_email_yn: form.signup_email_yn.checked ? 'Y' : 'N',
                signup_tel_yn: form.signup_tel_yn.checked ? 'Y' : 'N',
                password_find_mode: mode ? mode.value : '',
                log_keep_days: form.log_keep_days.value,
            },
            success: function(response) {
                showAlert({
                    type: 'success',
                    title: 'Complete',
                    text: response.msg,
                    callback: reload,
                });
            },
        });
    });
});
