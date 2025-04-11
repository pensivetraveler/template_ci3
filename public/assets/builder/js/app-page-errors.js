function getErrorNavTitle(type) {
    switch (type) {
        case 'php' : return 'PHP-Error-';
        case 'js' : return 'JS-Error-';
        default : return 'Exception-';
    }
}

function showErrorModal(modal, errors) {
    if(errors.length) {
        let staticModal = false;
        let loadedViews = [];

        if($(modal).data('init') !== '1') {
            $(modal).find('ul.nav.nav-tabs').empty();
            $(modal).find('div.tab-content').empty();
            $(modal).data('init', '1');
        }

        for(const [idx, error] of errors.entries()) {
            setErrorModalContent(modal, error, idx+1);
            loadedViews.push(...error.views.filter(view => !loadedViews.includes(view)));
            staticModal = error.static;
        }

        if(staticModal && !loadedViews.includes('header')) {
            // close button none
            $(modal).find('.btn-close').addClass('d-none');
            // home button show
            $(modal).find('.btn-home-wrap').removeClass('d-none');
            // static
            $(modal).modal({
                backdrop: 'static',
                keyboard: false,
            })
        }else{
            $('.nav-error').find('i').addClass('ri-error-warning-fill').removeClass('ri-error-warning-line');
            $('.nav-error').find('span').removeClass('d-none');
        }

        $(modal).modal('show');
    }else{
        $('#errorModal .modal-dialog').removeClass('modal-xl').addClass('modal-sm');
        $('#errorModal .card').addClass('d-none');
        $('#errorModal .no-error').removeClass('d-none');
        $('#errorModal .text-center').removeClass('mb-8');
        $('#errorModal .text-center h4').addClass('mb-0').text('No Error Occured');
    }
}

function setErrorModalContent(modal, error, key = 0) {
    const navTitle = getErrorNavTitle(error.type)+key.toString();
    const navId = 'navsError'+key.toString();
    const navClass = key === 1 ? 'active' : '';
    modal.querySelector('ul.nav.nav-tabs').innerHTML += `
						<li class="nav-item">
							<button
								type="button"
								class="nav-link ${navClass}"
								role="tab"
								data-bs-toggle="tab"
								data-bs-target="#${navId}"
								aria-controls="${navId}"
								aria-selected="false">
								${navTitle}
							</button>
						</li>
					`;

    const typeHead = error.type === 'php'
        ? 'A PHP Error was encountered'
        : 'An uncaught Exception was encountered';

    let summary = '';
    for(const [i, key] of Object.keys(error.summary).entries()) {
        const className = i === Object.keys(error.summary).length-1?'':'mb-2';
        const summaryKey = key.charAt(0).toUpperCase() + key.slice(1);
        summary += `
							<div class="d-flex align-items-center ${className}">
								<span class="fw-medium mx-2">${summaryKey}:</span>
								<span>${error.summary[key]}</span>
							</div>
						`;
    }

    let backtrace = '';
    for(const item of error.backtrace) {
        backtrace += `
							<li class="timeline-item timeline-item-transparent">
								<span class="timeline-point timeline-point-primary"></span>
								<div class="timeline-event">
									<div class="timeline-header mb-3">
										<h6 class="mb-0">File: ${item.file}</h6>
									</div>
									<p class="mb-2">Line: ${item.line}</p>
									<p>Function: ${item.func}</p>
								</div>
							</li>
						`;
    }

    const paneClass = key === 1 ? 'show active' : '';
    modal.querySelector('div.tab-content').innerHTML += `
						<div class="tab-pane fade ${paneClass}" id="${navId}" role="tabpanel">
							<div class="text-center mb-6">
								<p class="mb-2">${navTitle}</p>
								<h4 class="mb-4">${typeHead}</h4>
							</div>
							<div class="bg-lighter rounded-3 p-4 mb-6">
								${summary}
							</div>
							<div class="rounded-3 p-8 border" style="max-height: 600px;overflow-y: scroll">
								<div class="text-center mb-6">
									<p>Backtrace</p>
								</div>
								<hr class="mb-12">
								<ul class="timeline mb-0">
									${backtrace}
								</ul>
							</div>
						</div>
					`;
}

function parseErrorStackTrace(trace) {
    const parsedLines = [];

    if(trace !== null || trace.length === 0) {
        if(typeof trace !== 'string') return parsedLines;
        const lines = trace.trim().split('\n');

        // 정규표현식을 사용해 "at 함수명 (파일:라인번호:칼럼번호)" 부분 파싱
        const regex = /at\s+(.*?)\s+\((.*?):(\d+):(\d+)\)/;

        for (let line of lines) {
            const match = line.match(regex);
            if (match) {
                const [, functionName, fileName, lineNumber, columnNumber] = match;
                parsedLines.push({
                    file: fileName,
                    func: functionName || '<anonymous>',
                    line: Number(lineNumber),
                });
            }
        }
    }

    return parsedLines;
}

function getJavascriptErrorObject(message, source, lineno, colno, stack) {
    return {
        type : 'js',
        views : [],
        static : false,
        summary : {
            severity : 'Notice',
            lifeCycle : 'post_controller',
            message : message,
            filename : source,
            lineNumber : lineno,
        },
        backtrace : parseErrorStackTrace(stack)
    };
}

function setJavascriptErrorModal(message, source, lineno, colno, stack) {
    const modal = document.querySelector('#errorModal');

    if($(modal).data('init') !== '1') {
        $(modal).find('ul.nav.nav-tabs').empty();
        $(modal).find('div.tab-content').empty();

        $('#errorModal .modal-dialog').removeClass('modal-sm').addClass('modal-xl');
        $('#errorModal .card').removeClass('d-none');
        $('#errorModal .no-error').addClass('d-none');
        $('#errorModal .text-center').addClass('mb-8');
        $('#errorModal .text-center h4').removeClass('mb-0').text('Oops somthing went wrong.');

        $('.nav-error').find('i').addClass('ri-error-warning-fill').removeClass('ri-error-warning-line');
        $('.nav-error').find('span').removeClass('d-none');

        $(modal).data('init', '1');
    }

    const obj = getJavascriptErrorObject(message, source, lineno, colno, stack);
    const idx = modal.querySelectorAll('ul.nav.nav-tabs li').length;
    setErrorModalContent(modal, obj, idx+1);
    common.ERRORS.push(obj);
}

function customErrorHandler(error) {
    setJavascriptErrorModal(error.message, error.fileName, error.lineNumber, error.columnNumber, error.stack);
}
