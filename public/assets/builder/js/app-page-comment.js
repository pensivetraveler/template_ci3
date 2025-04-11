function resetCommentForm() {
	const formComment = document.getElementById('formComment');
	formComment.parent_id.value = 0;
	formComment.comment_id.value = 0;
	formComment.content.value = '';
	formComment.depth.value = 0;
	document.getElementById('target-comment-content').innerText = '';
	document.querySelector('.target-comment-wrap').setAttribute('data-loaded', 'false');
}

function getCommentBtns(comment, isReply = false) {
	let btnHTML = '';
	if(!isReply) btnHTML += `<button class="btn p-1 btn-outline-primary btn-comment btn-comment-reply">${getLocale('Reply', common.LOCALE)}</button>`;
	if(comment.mine_yn) {
		btnHTML += `
				<button class="btn p-1 btn-outline-primary btn-comment btn-comment-edit">${getLocale('Edit', common.LOCALE)}</button>
				<button class="btn p-1 btn-outline-danger btn-comment btn-comment-delete">${getLocale('Delete', common.LOCALE)}</button>
			`;
	}
	return btnHTML;
}

function setCommentList(comments) {
	const container = document.getElementById('comment-container');
	if(!container) return;

	const commentListWrap = document.getElementById('comment-list');
	commentListWrap.innerHTML = '';
	for(const comment of comments) {
		const date = comment.created_dt.substring(0, 10).replace(/-/g, '.');
		const time = comment.created_dt.substring(11, comment.created_dt.length);
		const btnHTML = getCommentBtns(comment);

		let replyList = '';
		for(const reply of comment.reply_list) {
			const replyDate = reply.created_dt.substring(0, 10).replace(/-/g, '.');
			const replyTime = reply.created_dt.substring(11, reply.created_dt.length);
			const replyBtns = getCommentBtns(reply, true);
			replyList += `
				<li class="ms-8 py-4" data-comment-id="${reply.comment_id}" data-mine-yn="${reply.mine_yn}" data-comment-depth="1">
					<i class="ri-corner-down-right-line"></i>
					<div class="d-flex comment-wrap">
						<div class="comment-info">
							<div class="w-px-200 me-4 comment-creater">${reply.creater.name} (${reply.creater.id})</div>
							<div class="flex-fill me-8 comment-content">${reply.content}</div>
						</div>
						<div class="d-flex justify-content-end align-items-center comment-btns">
							<div class="btn-wrap">${replyBtns}</div>
							<div class="text-end w-px-100 comment-time">${replyDate}<br>${replyTime}</div>
						</div>
					</div>
				</li>
			`;
		}

		const commentHTML = `
			<li class="py-6" data-comment-id="${comment.comment_id}" data-mine-yn="${comment.mine_yn}" data-commen-depth="0" data-is-admin="${comment.is_admin}">
				<div class="d-flex comment-wrap">
					<div class="comment-info">
						<div class="w-px-200 me-4 comment-creater">${comment.creater.name} (${comment.creater.id})</div>
						<div class="flex-fill me-8 comment-content">${comment.content}</div>
					</div>
					<div class="d-flex justify-content-end align-items-center comment-btns">
						<div class="btn-wrap">${btnHTML}</div>
						<div class="text-end w-px-100 comment-time">${date}<br>${time}</div>
					</div>
				</div>
				<ul class="comment-reply-list list-unstyled rounded-2">${replyList}</ul>
			</li>
		`;
		commentListWrap.innerHTML += commentHTML;
	}

	resetCommentForm();
}

function getCommentList() {
	$.ajax({
		url : '/api/comments',
		method : 'get',
		data : {
			article_id: document.getElementById('formComment').article_id.value,
		},
		success : function (response) {
			setCommentList(response.data);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			console.warn(jqXHR.responseJSON)
		},
	});
}

$(function() {
	const formComment = document.getElementById('formComment');

	// Form validation for Add new record
	let fv = FormValidation.formValidation(
		formComment,
		{
			fields: {
				content : {
					validators: {
						notEmpty: {
							message: 'The comment is required',
						}
					}
				},
			},
			plugins: {
				trigger: new FormValidation.plugins.Trigger(),
				bootstrap5: new FormValidation.plugins.Bootstrap5({
					// Use this for enabling/changing valid/invalid class
					// eleInvalidClass: '',
					eleValidClass: '',
					rowSelector: function(field, ele) {
						switch (field) {
							default:
								return '.form-validation-unit';
						}
					},
				}),
				submitButton: new FormValidation.plugins.SubmitButton(),
				// submit button의 type을 submit으로 원할 경우
				// defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
				autoFocus: new FormValidation.plugins.AutoFocus(),
			},
			init: instance => {
				instance.on('plugins.message.placed', function (e) {
					//* Move the error message out of the `input-group` element
					if (e.element.parentElement.classList.contains('input-group')) {
						// `e.field`: The field name
						// `e.messageElement`: The message element
						// `e.element`: The field element
						e.element.parentElement.insertAdjacentElement('afterend', e.messageElement);
					}
				});
			}
		}
	).on('plugins.message.displayed', function (event) {
		// e.messageElement presents the error message element
	}).on('core.field.init', function(event) {
		// When a field is initialized, bind the input event to it
		var field = event.field;
		var element = event.elements[0];  // The field element
		element.addEventListener('change', function() {
			// Revalidate field when flatpickr
			if(element.classList.contains('.form-input_date-flatpickr')) fv.revalidateField(field);
			// Revalidate field whenever input changes
			// e.fv.revalidateField(field);
		});
	}).on('core.form.validating', function(event) {
		// 유효성 검사 시작 전
		console.log('%c The form validation has started.', 'color: green')
		const form = event.formValidation.form;
		if(form['_event'] !== undefined) form['_event'].value = 'submit';
	}).on('core.validator.validating', function(event) {
		// 특정 요소에 대한 유효성 검사 시작 전
		console.log('============================================================');
		console.log('%c Validator for the field ' + event.field + ' is validating.', 'color: skyblue');
		if(event.element.hasAttribute('data-textarea-id')) {
			if(event.element.getAttribute('data-textarea-id')) {
				const textareaId = event.element.getAttribute('data-textarea-id');
				event.element.value = editors[`${textareaId}`].root.innerHTML;
			}
		}
		console.log('value : ', event.element.value);
	}).on('core.validator.validated', function(event) {
		// 특정 요소에 대한 유효성 검사 시작 후
		console.log('%c Validator for the field ' + event.field + ' is validated.', 'color: skyblue');
		if(!event.result.valid) {
			console.log('------------------------------------------------------------');
			console.log('%c Validator for the field ' + event.field + ' is invalid.', 'color: red');
			console.log('Invalid validator:', event.validator);
			console.log('Invalid field:', event.field);
			console.log('Error message:', event.result.message);
			console.log('Result Object:',event.result)
			console.log('------------------------------------------------------------');
		}
	}).on('core.form.valid', function(event) {
		// Send the form data to back-end
		// You need to grab the form data and create an Ajax request to send them
		submitAjax('#formComment', {
			url: '/api/comments'+(formComment.comment_id.value?'/'+formComment.comment_id.value:''),
			success: function(response) {
				showAlert({
					type: 'success',
					title: 'Complete',
					text: formComment['_mode'].value === 'edit' ? 'Your Data Is Updated' : 'Registered Successfully',
					callback: getCommentList,
				});
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.warn(jqXHR.responseJSON)
				if(jqXHR.status === 422) {
					jqXHR.responseJSON.errors.forEach(error => {
						if(fv.fields.hasOwnProperty(error.param)) {
							fv.updateFieldStatus(error.param, 'Invalid', customValidatorsPreset.inflector(error.type));
						}
					});
				}else{
					showAlert({
						type: 'warning',
						text: jqXHR.responseJSON.msg,
						callback: getCommentList,
					});
				}
			}
		});
	}).on('core.form.invalid', function () {
		// if fields are invalid
		console.log('core.form.invalid')
	});

	$('#comment-list').on('click', '.btn-comment', function (e) {
		const commentWrap = e.target.closest('li');
		const commentId = commentWrap.getAttribute('data-comment-id');
		resetCommentForm();

		if(e.target.classList.contains('btn-comment-reply')) {
			formComment.parent_id.value = commentId;
			formComment.depth.value = 1;
			document.getElementById('target-comment-content').innerText = makeEllipsis(commentWrap.querySelector('.comment-content').innerText, 20);
			document.querySelector('.target-comment-wrap').setAttribute('data-loaded', 'true');
			document.getElementById('target-comment-action').innerText = getLocale('Reply', common.LOCALE);
		}else{
			const mineYn = commentWrap.getAttribute('data-mine-yn');
			if(mineYn !== 'true') {
				showAlert({
					type: 'warning',
					text: getLocale("It's not my comment.", 'ko'),
				});
				return;
			}

			formComment.parent_id.value = 0;
			formComment.comment_id.value = commentId;

			if(e.target.classList.contains('btn-comment-edit')) {
				document.getElementById('target-comment-content').innerText = makeEllipsis(commentWrap.querySelector('.comment-content').innerText, 20);
				document.querySelector('.target-comment-wrap').setAttribute('data-loaded', 'true');
				formComment.content.value = commentWrap.querySelector('.comment-content').innerText;
				document.getElementById('target-comment-action').innerText = getLocale('Edit', common.LOCALE);
			}

			if(e.target.classList.contains('btn-comment-delete')) {
				Swal.fire({
					title: '<h5>'+getLocale('Do you really want to delete?', common.LOCALE)+'</h5>',
					showCancelButton: true,
					confirmButtonText: getLocale('Delete', common.LOCALE),
					cancelButtonText: getLocale('Cancel', common.LOCALE),
					customClass: {
						confirmButton: 'btn btn-danger me-3 waves-effect waves-light',
						cancelButton: 'btn btn-outline-secondary waves-effect'
					},
					buttonsStyling: false
				}).then(function (result) {
					if (result.isConfirmed) {
						executeAjax({
							url: '/api/comments/'+formComment.comment_id.value,
							method: 'delete',
							after : {
								callback: showAlert,
								params: {
									type: 'success',
									title: 'Complete',
									text: 'Delete Completed',
									callback: getCommentList,
								},
							}
						});
					}
				});
			}
		}
	});

	$('.btn-write-cancel').on('click', function(e) {
		resetCommentForm();
	});

	getCommentList();
});
