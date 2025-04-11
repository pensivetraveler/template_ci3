function getArticlePreference() {
	let result = null;
	$.ajax({
		async: false,
		url: '/api/articlePrefer/',
		headers: {
			'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
		},
		data: {
			article_id : common.KEY,
		},
		method: 'get',
		dataType: 'json',
		success: function (response, textStatus, jqXHR) {
			if(response.data.length) {
				result = response.data[0].prefer_cd;
				document.getElementById('pref001Cnt').innerHTML = response.data[0].pref001Cnt;
				document.getElementById('pref002Cnt').innerHTML = response.data[0].pref002Cnt;
				document.getElementById('pref003Cnt').innerHTML = response.data[0].pref003Cnt;
			}
		},
	});
	return result;
}

function activeArticePreference(prefCd) {
	switch (prefCd) {
		case '001' :
			$('.btn-pref-001').removeClass('btn-dribbble').addClass('btn-outline-dribbble').addClass('clicked');
			break;
		case '002' :
			$('.btn-pref-002').removeClass('btn-linkedin').addClass('btn-outline-linkedin');
			break;
		case '003' :
			$('.btn-pref-003').removeClass('btn-dark').addClass('btn-outline-dark').addClass('clicked');
			break;
	}
}

function inactiveArticlePreference(prefCd) {
	if(prefCd !== null) {
		switch (prefCd) {
			case '001' :
				$('.btn-pref-001').removeClass('btn-outline-dribbble').addClass('btn-dribbble');
				break;
			case '002' :
				$('.btn-pref-002').removeClass('btn-outline-linkedin').addClass('btn-linkedin');
				break;
			case '003' :
				$('.btn-pref-003').removeClass('btn-outline-dark').addClass('btn-dark');
				break;
		}
	}else{

	}
}

function setArticlePreference(prefCd = null) {
	if(prefCd === null) prefCd = getArticlePreference();

	if(prefCd !== null) {
		$('.btn-pref').removeClass('clicked');
		const smlCd = prefCd.substring(3, 6);
		if(smlCd === '001') {
			$('.btn-pref-001').removeClass('btn-dribbble').addClass('btn-outline-dribbble').addClass('clicked');
			$('.btn-pref-002').removeClass('btn-outline-linkedin').addClass('btn-linkedin');
			$('.btn-pref-003').removeClass('btn-outline-dark').addClass('btn-dark');
		}else if(smlCd === '002') {
			$('.btn-pref-001').removeClass('btn-outline-dribbble').addClass('btn-dribbble');
			$('.btn-pref-002').removeClass('btn-linkedin').addClass('btn-outline-linkedin').addClass('clicked');
			$('.btn-pref-003').removeClass('btn-outline-dark').addClass('btn-dark');
		}else {
			$('.btn-pref-001').removeClass('btn-outline-dribbble').addClass('btn-dribbble');
			$('.btn-pref-002').removeClass('btn-outline-linkedin').addClass('btn-linkedin');
			$('.btn-pref-003').removeClass('btn-dark').addClass('btn-outline-dark').addClass('clicked');
		}
	}
}

function removeArticlePreference(prefCd) {
	$.ajax({
		url: '/api/articlePrefer/',
		headers: {
			'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
		},
		data: {
			article_id : common.KEY,
		},
		method: 'delete',
		dataType: 'json',
		success: function (response, textStatus, jqXHR) {
			if(prefCd === '001') {
				$('.btn-pref-001').removeClass('btn-outline-dribbble').addClass('btn-dribbble').removeClass('clicked');
			}else if(prefCd === '002') {
				$('.btn-pref-002').removeClass('btn-outline-linkedin').addClass('btn-linkedin').removeClass('clicked');
			}else {
				$('.btn-pref-003').removeClass('btn-outline-dark').addClass('btn-dark').removeClass('clicked');
			}

			const num = parseInt($(`.btn-pref-${prefCd}`).find('span').text());
			if(num > 1) {
				getArticlePreference();
			}else{
				$(`.btn-pref-${prefCd}`).find('span').text(num-1);
			}

			showAlert({
				type: 'success',
				text: jqXHR.responseJSON.msg,
			});
		},
		error: function (jqXHR, textStatus, errorThrown) {
			showAlert({
				type: 'warning',
				text: jqXHR.responseJSON.msg,
			});
		}
	});
}

function submitArticlePreference(prefCd) {
	$.ajax({
		url: '/api/articlePrefer/',
		headers: {
			'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
		},
		data: {
			article_id : common.KEY,
			prefer_cd : prefCd,
		},
		method: 'put',
		dataType: 'json',
		success: function (response, textStatus, jqXHR) {
			setArticlePreference(prefCd);
			showAlert({
				type: 'success',
				text: jqXHR.responseJSON.msg,
			});
			getArticlePreference();
		},
		error: function (jqXHR, textStatus, errorThrown) {
			showAlert({
				type: 'warning',
				text: jqXHR.responseJSON.msg,
			});
		}
	});
}

$(function() {
	if($('body').data('method') === 'view') {
		$('.btn-pref').on('click', function (e) {
			const prefCd = $(this).data('pref-cd');
			if (this.classList.contains('clicked')) {
				removeArticlePreference(prefCd);
			} else {
				submitArticlePreference('PRF' + prefCd);
			}
		});

		setArticlePreference();
	}
})
