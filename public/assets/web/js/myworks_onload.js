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

		if($('body').data('method') === 'edit') {
			const isMine = isMyData(common.KEY, false);
			if(!isMine || document.getElementById('formRecord').open_yn.value === 'Y') {
				$('.btn-work-temporary').addClass('d-none');
			}
		}
	}
})
