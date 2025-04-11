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
	if($('body').data('method') === 'add' || $('body').data('method') === 'edit') {
		const previewTemplate = `<div class="dz-preview dz-file-preview">
<div class="dz-details">
  <div class="dz-thumbnail">
    <img data-dz-thumbnail>
    <span class="dz-nopreview">No preview</span>
    <div class="dz-success-mark"></div>
    <div class="dz-error-mark"></div>
    <div class="dz-error-message"><span data-dz-errormessage></span></div>
    <div class="progress">
      <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
    </div>
  </div>
  <div class="dz-filename" data-dz-name></div>
  <div class="dz-size" data-dz-size></div>
</div>
</div>`;

		const dropzoneFull = document.querySelector('#fullpage-dropzone');
		if (dropzoneFull) {
			Dropzone.autoDiscover = false;
			const myDropzone = new Dropzone(dropzoneFull, {
				url: dropzoneFull.closest('form').action,
				autoProcessQueue: false,
				thumbnailWidth: null,
				thumbnailHeight: null,
				previewTemplate: previewTemplate,
				parallelUploads: 1,
				maxFilesize: 5,
				addRemoveLinks: true,
				maxFiles: 1,
				acceptedFiles: "image/*", // 허용 파일 형식
				dictDefaultMessage: "파일을 드래그하거나 클릭하여 업로드하세요",
				dictRemoveFile: "삭제",
				init: function () {
					this.on("success", function (file, response) {
						console.log("업로드 성공:", response);
					});
					this.on("error", function (file, errorMessage) {
						console.log("업로드 실패:", errorMessage);
					});
					this.on('addedfile', function(file) {
						if (this.files.length > 1) {
							this.removeFile(this.files[0]);
						}
						const previewContainer = document.getElementById("fullpage-dropzone");
						previewContainer.appendChild(file.previewElement); // 이미지 미리보기만 추가
					});
					this.on("sending", function (file, errorMessage) {
						console.log("업로드");
					});
				},
			});
		};

		const isMine = isMyData(common.KEY, false);
		if(!isMine || document.getElementById('formRecord').open_yn.value === 'Y') {
			$('.btn-work-temporary').addClass('d-none');
		}

		$('.btn-work-temporary').on('click', function(e) {
			e.preventDefault();
			document.getElementById('formRecord').open_yn.value = 'N';
			fv.validate();
		});

		$('.btn-work-share').on('click', function(e) {
			e.preventDefault();
			document.getElementById('formRecord').open_yn.value = 'Y';
			fv.validate();
		});
	}

	if($('body').data('method') === 'view') {
		$('.btn-pref').on('click', function(e) {
			const prefCd = $(this).data('pref-cd');
			const isMine = isMyData(common.KEY, false);
			if(!isMine) {
				if(this.classList.contains('clicked')) {
					removeArticlePreference(prefCd);
				}else{
					submitArticlePreference('PRF'+prefCd);
				}
			}else{
				showAlert({
					type: 'warning',
					text: getLocale('You can not vote to your data', common.LOCALE),
				});
			}
		});

		setArticlePreference();
	}
})
