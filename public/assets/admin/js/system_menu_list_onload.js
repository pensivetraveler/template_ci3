$(function () {
    document.querySelector('#formRecord').addEventListener('refreshPlugins', function (e) {
        setSelect2Readonly('#form_page-class', true);
        setSelect2Readonly('#form_page-method', true);

        if(Object.hasOwn(e.detail, 'record') && e.detail.record !== null) {
            if(parseInt(e.detail.record.is_sub_menu)) {
                setSelect2Readonly('#form_page-class');
                setSelect2Readonly('#form_page-method');
            }
        }
    });
});
