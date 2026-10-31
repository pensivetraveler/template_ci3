function afterChangeUserKind(node) {
    common.API_PARAMS.grade_cd = node.value;
}

/**
 * "기본 권한으로 채우기": 고른 등급(grade_cd)·파트너(partner_id)에 권한 행이 없는 메뉴만 기본 권한으로 만든다 (있는 행은 그대로)
 * 목록 조회는 행을 만들지 않으므로, 처음 설정할 때 이 버튼으로 한 번에 채운다
 */
function fillDefaultAuth(btn, params = null) {
    const field = params && params.field ? params.field : 'grade_cd';
    const input = document.querySelector(`#formFilter [name$="[${field}]"], #formFilter [name="${field}"]`);
    const value = input ? input.value : (common.API_PARAMS[field] ?? '');
    if(!value) {
        showAlert({type: 'warning', text: getLocale('Select a target first', common.LOCALE)});
        return;
    }

    Swal.fire({
        title: getLocale('Fill Default Auth', common.LOCALE),
        text: getLocale('Menus without saved auth will get the default auth.', common.LOCALE),
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: getLocale('Confirm', common.LOCALE),
        cancelButtonText: getLocale('Cancel', common.LOCALE),
        customClass: {
            confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
            cancelButton: 'btn btn-outline-secondary waves-effect'
        },
        buttonsStyling: false
    }).then(function (result) {
        if(!result.isConfirmed) return;
        executeAjax({
            url: common.API_URI + '/fillDefault',
            method: 'post',
            data: {[field]: value},
            success: function(response) {
                const filled = response.data && response.data[0] ? response.data[0].filled : 0;
                showAlert({
                    type: 'success',
                    title: 'Complete',
                    text: `${getLocale('Filled', common.LOCALE)} ${filled}`,
                    callback: function() { $('.datatables-records').DataTable().ajax.reload(null, false); },
                });
            },
        });
    });
}

function resetMenuAuthDB() {
    executeAjax({
        url: common.API_URI + '/' + 'reset',
        method: 'post',
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

function renderMenuAuthCheckbox(data, type, full, meta, column, param) {
    const isChangeable = !full.is_show ? 0 : isAuthChangeable(full.base_auth, column.field);
    const wrap = document.createElement('label');
    wrap.classList.add('d-inline-block')

    // 서버 값을 HTML 문자열로 잇지 않고 노드로 만든다 (변경은 app-page-list.js 의 위임 이벤트)
    const inner = createListCheckbox(full, column, data === 1, !isChangeable);

    return renderColumnHTML(data, full, column, wrap, inner);
}

function isAuthChangeable(baseAuth, mode) {
    const map = {
        create : 0,
        read   : 1,
        update : 2,
        delete : 3,
        export : 4,
        import : 5,
    }

    if(!map.hasOwnProperty(mode)) return 0;

    return parseInt(baseAuth.substring(map[mode], map[mode]+1));
}

