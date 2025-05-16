function setViewCount() {
	executeAjax({
		async: false,
		url: '/api/articleRead/',
		method: 'put',
		headers: {
			'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
		},
		data: isObject(common.KEY) ? common.KEY : {
			article_id : common.KEY,
		},
		dataType: 'json',
		success: function (response, textStatus, jqXHR) {
			console.log(response)
		},
		error: function (jqXHR, textStatus, errorThrown) {
			// showAlert({
			// 	type: 'warning',
			// 	text: jqXHR.responseJSON.msg,
			// });
		},
	});
}

$(function() {
	if($('body').data('method') === 'view') setViewCount();

	$('.btn-article-list').on('click', function(e) {
		location.href = common.PAGE_LIST_URI;
	});

	$('.btn-article-edit').on('click', function(e) {
		if(isMyData(common.KEY)) {
			location.href = getUrlWithIdentifiers(common.PAGE_EDIT_URI, common.KEY)
		}
	});

	$('.btn-article-delete').on('click', function(e) {
		if(!common.KEY) throw new Error(`KEY is not defined`);
		if(isMyData(common.KEY)) {
			deleteData(common.KEY, {
				callback: redirect,
				params: common.PAGE_LIST_URI,
			});
		}
	});
});
