function afterChangeUserKind(node) {
    common.API_PARAMS.grade_cd = node.value;
}

function resetMenuAuthDB() {
    executeAjax({
        url: common.API_URI + '/' + 'reset',
        headers : {
            'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
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
}

function renderMenuAuthCheckbox(data, type, full, meta, column, param) {
    const isChangeable = !full.is_show ? 0 : isAuthChangeable(full.base_auth, column.field);
    const wrap = document.createElement('label');
    wrap.classList.add('d-inline-block')

    const idData = getIdentifiersData(full, common.IDENTIFIER, true).replace(/"/gi, "'");
    const inner = `
        <input
            type="checkbox"
            value="1"
            ${data===1?'checked':''}
            onchange="afterCheckboxColumnChange(${idData}, '${column.field}', this.checked?1:0)"
            class="form-check-input"
            ${isChangeable?'':'disabled'}
        >
    `;

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

