/**
 * 상세 보기(view) 화면
 * - common.VIEW_COLUMNS 의 필드마다 API 응답 값을 #{필드 id} 요소에 넣는다
 * - 값은 textContent 로 넣는다 (응답 값이 HTML 로 해석되지 않도록)
 * - 선택형 필드(options 가 있는 필드)는 값 대신 라벨을, 파일 필드는 다운로드 링크를 보여 준다
 * - appPlugins.view[필드] 가 함수면 기본 표시 대신 그 함수를 부른다: (element, value, data, column)
 * - 상세 페이지(#view-container)와 목록의 모달 상세(view_modal.php, app-page-list.js openViewModal)가 함께 쓴다
 */

function createViewFileLink(item) {
	const wrap = document.createElement('div');
	wrap.className = 'form-list-item d-flex align-items-center';

	const badge = document.createElement('div');
	badge.className = 'badge text-body text-truncate';

	const link = document.createElement('a');
	link.href = `${common.CURRENT_URI}/downloader/${encodeURIComponent(item.file_id)}`;

	const icon = document.createElement('i');
	icon.className = 'ri-file-download-fill ri-16px align-middle';

	const name = document.createElement('span');
	name.className = 'h6 mb-0 align-middle';
	name.textContent = item.orig_name ?? item.file_name ?? '';

	link.append(icon, name);
	badge.append(link);
	wrap.append(badge);
	return wrap;
}

function isViewFileList(value) {
	const list = isArray(value) ? value : [value];
	return list.length > 0 && list.every((item) => item !== null && typeof item === 'object' && Object.hasOwn(item, 'file_id'));
}

/**
 * 선택형 값의 라벨. options 는 {값: 라벨} 또는 [{id, text}] 형태
 */
function getViewOptionLabel(options, value) {
	if(isArray(options)) {
		const found = options.find((option) => option !== null && typeof option === 'object' && String(option.id ?? option.value) === String(value));
		return found ? (found.text ?? found.label ?? value) : value;
	}
	if(options !== null && typeof options === 'object' && Object.hasOwn(options, value)) return options[value];
	return value;
}

function formatViewValue(column, value) {
	if(value === null || value === undefined || value === '') return '';
	const options = column.options ?? null;
	if(options === null || (typeof options === 'object' && Object.keys(options).length === 0)) return value;

	// 다중 선택(체크박스 등)은 구분자로 나눠 각각 라벨로 바꾼다
	const seperator = column.option_attributes?.seperator ?? ',';
	const values = isArray(value) ? value : String(value).split(seperator);
	return values.map((item) => getViewOptionLabel(options, String(item).trim())).join(`${seperator} `);
}

function renderViewValue(element, column, value) {
	if(isViewFileList(value)) {
		element.replaceChildren(...(isArray(value) ? value : [value]).map(createViewFileLink));
		element.classList.remove('d-none');
		return;
	}

	if(element.tagName === 'SPAN' || element.tagName === 'DIV' || element.tagName === 'P') {
		const text = formatViewValue(column, value);
		element.textContent = text === '' ? '-' : text;
	}else if(value !== null && value !== undefined) {
		element.value = value;
	}
}

/**
 * 데이터를 불러와 container 안의 요소에 채운다. 채운 데이터를 돌려준다 (없으면 null)
 */
function applyViewData(dataId, container = document.getElementById('view-container')) {
	if(!container) return null;
	const data = getData(dataId, {
		_mode : 'view',
	});
	if(!data) return null;

	const columns = isArray(common.VIEW_COLUMNS) ? common.VIEW_COLUMNS : [];
	const plugins = (typeof appPlugins !== 'undefined' && appPlugins.view) ? appPlugins.view : {};
	const handled = new Set();

	columns.forEach((column) => {
		if(!column.field) return;
		handled.add(column.field);
		const element = container.querySelector(`#${CSS.escape(column.id ?? column.field)}`);
		if(element === null) return;

		const value = data[column.field];
		if(typeof plugins[column.field] === 'function') {
			plugins[column.field](element, value, data, column);
			return;
		}

		if(column.field === 'thumbnail') {
			// 대표 이미지는 배경으로 표시
			if(isArray(value) && value.length && value[0].file_link) {
				element.style.backgroundImage = `url("${encodeURI(value[0].file_link)}")`;
				element.classList.remove('none');
			}else{
				// 모달은 같은 요소를 다시 쓰므로 이전 값을 지운다
				element.style.backgroundImage = '';
				element.classList.add('none');
			}
			return;
		}

		renderViewValue(element, column, value);
	});

	// VIEW_COLUMNS 에 없지만 화면에 같은 id 요소가 있는 값(전용 뷰의 제목 등)도 채운다
	Object.keys(data).forEach((key) => {
		if(handled.has(key)) return;
		const element = container.querySelector(`#${CSS.escape(key)}`);
		if(element === null) return;
		if(typeof plugins[key] === 'function') {
			plugins[key](element, data[key], data, {field: key});
			return;
		}
		renderViewValue(element, {field: key}, data[key]);
	});

	const form = container.querySelector('.view-form');
	if(form){
		// 모달은 열 때마다 다시 채우므로 플러그인 준비는 한 번만 (폼 자산이 없는 화면이면 건너뜀)
		if(typeof preparePlugins === 'function' && !form.dataset.pluginsReady) {
			preparePlugins(form);
			form.dataset.pluginsReady = '1';
		}

		form.dispatchEvent(
			new CustomEvent('fetchViewData', {
				bubbles : false,
				cancelable : true,
				composed : false,
				detail: { record: data },
			}),
		);

		if(typeof refreshPlugins === 'function') refreshPlugins(form);
	}

	document.body.setAttribute('data-onload', true);
	return data;
}

function setButtonsDisplay() {
	// 수정 화면이 없으면(사이드 폼으로 수정하는 화면 등) 수정 버튼을 숨김
	if(!common.PAGE_EDIT_URI) $('.btn-view-edit').remove();

	// 작성자만 수정·삭제할 수 있는 화면(method properties.checkOwner)
	if(common.VIEW_CHECK_OWNER && !isMyData(common.KEY, false)) {
		$('.btn-view-edit').remove();
		$('.btn-view-delete').remove();
	}
}

$(function() {
	// 상세 페이지에서만 (목록의 모달 상세는 openViewModal 이 부른다)
	if(!document.getElementById('view-container')) return;

	applyViewData(common.KEY);

	setButtonsDisplay();

	$('.btn-view-list').on('click', function(e) {
		location.href = common.PAGE_LIST_URI;
	});

	$('.btn-view-edit').on('click', function(e) {
		if(common.PAGE_EDIT_URI) location.href = getUrlWithIdentifiers(common.PAGE_EDIT_URI, common.KEY);
	});

	$('.btn-view-delete').on('click', function(e) {
		if(!common.KEY) throw new Error(`KEY is not defined`);
		deleteData(common.KEY, {
			callback: redirect,
			params: common.PAGE_LIST_URI,
		});
	});
});
