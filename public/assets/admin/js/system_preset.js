appPlugins.form.select2 = {
    'class' : {
        onHandler: {
            change(e) {
                if($('[name="class"]').val()) {
                    setDynamicSelect2Options(
                        '[name="class"]',
                        {
                            url: getUrlWithIdentifiers(common.API_URI+'/options', {}, {
                                class: $('[name="class"]').val()
                            }),
                            target: '[name="method"]',
                        }
                    )
                }
            },
        }
    },
};
