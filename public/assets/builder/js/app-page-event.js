$(function() {
    if(form = document.querySelector('#formRecord')) {
        form.querySelectorAll('input, textarea, select').forEach(function(node) {
            if(node.type === 'hidden') return;
            node.addEventListener('change', function(e) {
                node.setAttribute('data-input-changed', 'true');
            })
        });
    }

    // On hiding modal, remove iframe video/audio to stop playing
    $('#youTubeModal').on('show.bs.modal', (e) => {
        $(this).find('iframe').attr('src', '//www.youtube.com/embed/'+e.relatedTarget.getAttribute('data-value')+'?autoplay=1');
    })
    $('#youTubeModal').on('hidden.bs.modal', (e) => {
        $(this).find('iframe').removeAttr('src');
    })

    $('#profilerModal').on('show.bs.modal', (e) => {
        if($('#codeigniter_profiler').length) {
            $('#profilerModal').find('ul.nav.nav-tabs').empty();
            $('#profilerModal').find('div.tab-content').empty();

            $('#codeigniter_profiler fieldset').each((k, v) => {
                // title
                let legend = $(v).find('legend').text().trim();
                let id, title, query, active, selected, contentActive;
                if(legend.indexOf(':')) {
                    title = legend.split(':')[0];
                    query = legend.replace(title+': ', '');
                }else{
                    title = legend;
                }
                title = title.replace('(보기)', '').trim();
                active = k === 0?'active':'';
                selected = k === 0?'true':'false';
                id = title.replace(/ /g, '-');

                const button = $('<button/>').attr({
                    'type' : "button",
                    'class' : `nav-link ${active}`,
                    'role' : "tab",
                    'data-bs-toggle' : "tab",
                    'data-bs-target' : `#navs-left-${id}`,
                    'aria-controls' : `navs-left-${id}`,
                    'aria-selected' : selected,
                }).text(title);
                const list = $('<li/>').addClass('nav-item').append(button);
                $('#profilerModal').find('ul.nav.nav-tabs').append(list);

                // content
                contentActive = k === 0?'show active':'';
                const innerContent = $(v).find('legend + *').css('display', '')[0].outerHTML;
                const content = $('<div/>').attr({
                    'class' : `tab-pane fade ${contentActive}`,
                    'id' : `navs-left-${id}`,
                    'role' : 'tabpanel',
                }).append(innerContent);
                $('#profilerModal').find('div.tab-content').append(content);
            });

            $('#codeigniter_profiler').remove();
        }
    });

    $('.select2, .select2-repeater').on('select2:select', function (e) {
        document.querySelector(`#${e.target.id}`).setAttribute('data-input-changed', true);
    });

    $('.select2, .select2-repeater').on('select2:unselect', function (e) {
        document.querySelector(`#${e.target.id}`).setAttribute('data-input-changed', false);
    });

	// selectpicker label 관련 class 제어
	$('body').on('show.bs.select', '.selectpicker', function() {
		$(this).closest('.form-floating').addClass('form-floating-bootstrap-select-label');
	});

	$('body').on('hide.bs.select', '.selectpicker', function() {
		$(this).closest('.form-floating').removeClass('form-floating-bootstrap-select-label');
	});

	// selectpicker 의 중복 호출에 대한 제어
	$('body').on('refreshed.bs.select', '.selectpicker', function (e, clickedIndex, isSelected, previousValue) {
	    const value = $(this).val();
	    let obj = [];
	    for(const option of [].slice.call(this.options)) {
	        if(!option.classList.contains('bs-title-option')) {
	            obj.push({
	                value : option.value,
	                text : option.text,
	            })
	        }
	    }
	    $(this).selectpicker('destroy').addClass('selectpicker').selectpicker('val', value);
	});

	window.addEventListener('addRepeaterItem', function (e) {
		console.log('Repeater item is added.');

		const node = e.detail.node;

		// select2-repeater
		if($(node).find('.select2-repeater').length > 0){
			$('.select2-container').remove();
			$('.select2-repeater.form-select').select2({
				placeholder: 'Placeholder text'
			});
			$('.select2-container').css('width', '100%');
			select2Focus($(node));
			$('.position-relative .select2-repeater').each(function () {
				$(this).select2({
					dropdownParent: $(this).closest('.position-relative')
				});
			});
		}

		// selectpicker
		if($(node).find('.selectpicker').length > 0){
			// Reinitialize selectpicker for the new row
			$(node).find('.form-floating-bootstrap-select').each((k, node) => {
				$(node).removeClass('form-floating-bootstrap-select');
				$(node).html($(node).find('select')[0].outerHTML+$(node).find('.bootstrap-select+label')[0].outerHTML);
				$(node).find('select.selectpicker option.bs-title-option').remove();
				$(node).find('select.selectpicker').selectpicker();
				$(node).addClass('form-floating-bootstrap-select');
			});
		}

		// flatpickr
		if($(node).find('.flatpickr').length > 0){
			$(node).find('.flatpickr').each((k, node) => {
				setFlatpickr(node);
			});
		}
	});

	// [data-btn-type] 중복검사
	$('[data-btn-type="dup_check"]').on('input', function(e) {
		const btn = document.querySelector(`[data-rel-field="${this.name}"].btn-dup-check`);
		const hidden = this.closest('form').querySelector(`[name="${this.name}_unique"]`);
		if(hidden) hidden.value = '';
		if(this.hasAttribute('data-original-value')) {
			if(btn && this.getAttribute('data-original-value')) {
				if(this.value === this.getAttribute('data-original-value')){
					hidden.value = 1;
					btn.setAttribute('disabled', 'disabled');
				}else{
					btn.removeAttribute('disabled');
				}
			}
		}else{
			btn.removeAttribute('disabled');
		}
	});

	// [data-show-if-field] value 에 따른 show hide 제어
	$('body').on('change', '[data-show-if-field]', function(e) {
		const target = $(e.target).data('show-if-field');
		const condition = $(e.target).data('show-if-value');
		if(condition == e.target.value) {
			$(e.target).closest('form').find(`.form-validation-unit:has([name="${target}"])`).removeClass('d-none');
		}else{
			$(e.target).closest('form').find(`.form-validation-unit:has([name="${target}"])`).addClass('d-none');
		}
	});

	// $('body').on('input', 'input, textarea', function(e) {
	//     if($(this).attr('max')) {
	//         const max = parseInt(this.getAttribute('max'));
	//         if(max > 0 && this.value.length > max) {
	//             this.value = this.value.substring(0, max);
	//             $(this).siblings('.form-text').text(`${max}글자 이하 입력해주세요.`).removeClass('d-none').focus();
	//         }else{
	//             $(this).siblings('.form-text').addClass('d-none');
	//         }
	//     }
	//
	//     if($(this).data('add-hypen')) {
	//         if(this.value.length >= 9) this.value = this.value.replace(/[^0-9]/g, "").replace(/^(\d{2,3})(\d{3,4})(\d{4})$/, `$1-$2-$3`);
	//     }
	//
	//     if($(this).data('text-type')) {
	//         const type = $(this).data('text-type').split('|');
	//         var regex;
	//         if(type.includes('eng') && type.includes('num')) {
	//             regex = /[^0-9a-zA-Z]/g;
	//         } else if (type.includes('eng')) {
	//             regex = /[^a-zA-Z]/g;
	//         } else if (type.includes('num')) {
	//             regex = /[^0-9]/g;
	//         }
	//         this.value = this.value.replace(regex, "");
	//     }
	// });

	// $('body').on('change', 'input, textarea, select', function(e) {
	//     if($(this).attr('minlength')) {
	//         const minlength = parseInt(this.getAttribute('minlength'));
	//         if(minlength > 0 && this.value.length < minlength) {
	//             $(this).siblings('.form-text').text(`${minlength}글자 이상 입력해주세요.`).removeClass('d-none').focus();
	//         }else{
	//             $(this).siblings('.form-text').addClass('d-none');
	//         }
	//     }
	//
	//     if($(this).data('dup-check')){
	//         let params;
	//         try {
	//             params = JSON.parse($(this).data('dup-check'))
	//         } catch (e) {
	//             params = JSON.parse($(this).data('dup-check').replace(/'/g, '"'));
	//         }
	//
	//         const value = $(this).val();
	//         const formText = $(this).siblings('.form-text');
	//         $.ajax({
	//             url: `${common.API_URI}/auth/dupCheck`,
	//             data: {key: params.key, value: value},
	//             headers: {
	//                 'Authorization' : common.HOOK_PHPTOJS_VAR_TOKEN,
	//             },
	//             method: 'get',
	//             dataType: 'json',
	//             success: function(json) {
	//                 console.log(json)
	//                 if(json.code === 2000) {
	//                     formText.addClass('d-none');
	//                 }else{
	//                     formText.text(json.msg).removeClass('d-none');
	//                 }
	//             },
	//             error: function(jqXHR, textStatus, errorThrown) {
	//                 let msg = jqXHR.responseJSON.msg;
	//                 if(jqXHR.responseJSON.code === 4090 && param.title) msg = msg.replace('데이터가', Josa.r(param.title, '이/가'));
	//                 formText.text(msg).removeClass('d-none').focus();
	//             }
	//         });
	//     }
	// });

})
