$(function() {
	$('.btn-withdraw').on('click', function (e) {
		Swal.fire({
			title: '<h5>'+getLocale('Do you really want to withdraw?', common.LOCALE)+'</h5>',
			showCancelButton: true,
			confirmButtonText: getLocale('Yes', common.LOCALE),
			cancelButtonText: getLocale('No', common.LOCALE),
			customClass: {
				confirmButton: 'btn btn-outline-danger me-3 waves-effect waves-red',
				cancelButton: 'btn btn-outline-secondary waves-effect waves-grey'
			},
			buttonsStyling: false
		}).then(function (result) {
			if (result.isConfirmed) {
				$.ajax({
					async: false,
					url: '/api/auth/withdraw',
					method: 'post',
					headers: {
						'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
					},
					success: function (response, textStatus, jqXHR) {
						console.log(response.msg)
						Swal.fire({
							type: 'success',
							html: '<span style="color: #e64542">탈퇴</span> 처리 되었습니다.<br>그동안 고생 많았어요.',
							customClass: {
								confirmButton: 'btn btn-primary waves-effect waves-light'
							},
							buttonsStyling: false,
						}).then(function (result) {
							location.href = '/';
						});
					},
					error: function (jqXHR, textStatus, errorThrown) {
						console.log(jqXHR);
					},
				});
			}
		});
	});
});
