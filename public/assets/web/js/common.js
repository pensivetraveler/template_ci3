function readMessage(messageId, articleId) {
	$.ajax({
		url: '/api/articles/message_read/'+messageId,
		headers: {
			'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
		},
		method: 'patch',
		dataType: 'json',
		success: function(response, textStatus, jqXHR) {
			location.href = '/works/view/'+articleId;
		},
		error: function (jqXHR, textStatus, errorThrown) {
			showAlert({
				type: 'warning',
				text: jqXHR.responseJSON.msg,
			});
		}
	});
}

$(function(){
	if(document.querySelectorAll('.dropdown-notifications-list').length)
		new PerfectScrollbar(document.querySelector('.dropdown-notifications-list'));
})
