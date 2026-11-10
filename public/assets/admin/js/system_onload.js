$(function () {
    if(document.querySelector('#formRecord')) {
        document.querySelector('#formRecord').addEventListener('applyFrmValues', function (e) {
            if(Object.hasOwn(e.detail, 'record') && e.detail.record !== null) {
                if(e.detail.record.class) {
                    const value = e.detail.record.class.toLowerCase();
                    const options = document.querySelectorAll("#form_page-class option");
                    options.forEach(opt => {
                        if (value === opt.value) {
                            $('#form_page-class').val(opt.value).trigger('change');
                        }
                    });
                }
            }
        });

        document.querySelector('#formRecord').addEventListener('refreshPlugins', function (e) {
            setSelect2Readonly('#form_page-class', true);
            setSelect2Readonly('#form_page-method', true);

            if(Object.hasOwn(e.detail, 'record') && e.detail.record !== null) {
                const isSubMenu = parseInt(e.detail.record.is_sub_menu) === 1;
                if(e.detail.record.class && !isSubMenu) {
                    const url = getUrlWithIdentifiers(common.API_URI+'/options', {}, {
                        class: e.detail.record.class
                    });
                    reloadSelect2('#form_page-method', url, [$('#form_page-class').val()]);
                }else{
                    if(isSubMenu) {
                        setSelect2Readonly('#form_page-class');
                        setSelect2Readonly('#form_page-method');
                    }
                    resetSelect2('#form_page-class', true);
                    resetSelect2('#form_page-method', true);
                }
            }
        });
    }
});
