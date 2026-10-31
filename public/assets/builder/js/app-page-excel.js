/**
 * 서버 검증 오류(422) 표시. errors: [{param: 필드, value: 엑셀 행 번호, msg}]
 * 해당 셀에 edit-required 를 붙이고, 앞의 몇 건을 알림에 글자로 보여 준다
 */
function emitExcelErrors(errors) {
	const list = Array.isArray(errors) ? errors : [];
	list.forEach((error) => {
		const tr = document.querySelector(`#inline-editable tbody tr[data-row="${CSS.escape(String(error.value ?? ''))}"]`);
		if(!tr) return;
		const index = getExcelColumnIndex(error.param);
		const td = index === -1 ? null : tr.children[index];
		(td ?? tr).classList.add('edit-required');
	});

	document.getElementById('totalErrorCount').innerText = String(document.querySelectorAll('#inline-editable td.edit-required, #inline-editable tr.edit-required').length);
	document.getElementById('btnErrorFind').removeAttribute('disabled');
	document.getElementById('excelFormSubmit').setAttribute('disabled', 'disabled');

	const wrap = document.createElement('div');
	list.slice(0, 5).forEach((error) => {
		const p = document.createElement('p');
		p.className = 'mb-1 text-start';
		const label = (excelHeaders.find(item => item.field === error.param) ?? {}).label ?? error.param ?? '';
		p.textContent = `${error.value}행${label ? ' ' + label : ''} : ${error.msg ?? ''}`;
		wrap.appendChild(p);
	});
	if(list.length > 5) {
		const more = document.createElement('p');
		more.className = 'mb-0 text-muted small';
		more.textContent = `외 ${list.length - 5}건`;
		wrap.appendChild(more);
	}

	showAlert({
		type : 'error',
		html: wrap.innerHTML,
	});
}

/**
 * 표에서 필드의 열 번호 (thead th[data-field])
 */
function getExcelColumnIndex(field) {
	const ths = Array.from(document.querySelectorAll('#inline-editable thead th'));
	return ths.findIndex(th => th.getAttribute('data-field') === field);
}

function handleExcelAjaxError(xhr) {
	if(xhr.status === 422 && xhr.responseJSON && Array.isArray(xhr.responseJSON.errors)) {
		emitExcelErrors(xhr.responseJSON.errors);
	}else{
		showAlert({
			type: "error",
			text: xhr.responseJSON ? xhr.responseJSON.msg : "Unknown Error",
		});
	}
}

function getEditableColumns(tableSelector, excludeIndices = [0]) {
	let editableColumns = [];
	let columnCount = $(tableSelector + " thead th").length; // 전체 컬럼 개수
	excludeIndices.push(columnCount - 1);
	$(tableSelector + " thead th").each(function (index) {
		if (!excludeIndices.includes(index)) { // 제외할 컬럼(기본 ID 컬럼) 제외
			editableColumns.push([index, "col" + index]);
		}
	});
	return editableColumns;
}

function resetExcelFile() {
	executeAjax({
		url: common.API_URI + '/deleteExcelFile',
		method: "PATCH",
		data: {
			class: document.body.getAttribute('data-class'),
		},
		success: function(response, textStatus, jqXHR) {
			showAlert({
				type: "success",
				title: 'Complete',
				text: 'Reset Completed',
				callback: reload,
			});
		},
		error: function(jqXHR, textStatus, errorThrown) {
			console.log(jqXHR)
			showAlert({
				type: "error",
				text: jqXHR.responseJSON ? jqXHR.responseJSON.msg : "Unknown Error",
			});
		},
	});
}

async function validateData(dataArray, chunkSize = 50) {
	for(let index = 0; index < dataArray.length; index += chunkSize) {
		const chunk = dataArray.slice(index, index + chunkSize).map(item => item.data);
		try {
			await $.ajax({
				url: common.API_URI + '/validateExcel',
				type: "POST",
				data: JSON.stringify(chunk),
				contentType: "application/json",
			});
		} catch (xhr) {
			handleExcelAjaxError(xhr);
			return false;
		}
	}
	return true;
}

/**
 * 50행씩 저장한다. 저장된 행은 submit-completed 로 표시해, 중간에 실패해도 다시 저장할 때 보내지 않는다.
 */
async function submitData(dataArray, chunkSize = 50) {
	for(let index = 0; index < dataArray.length; index += chunkSize) {
		const items = dataArray.slice(index, index + chunkSize);
		try {
			await $.ajax({
				url: common.API_URI + '/uploadExcel',
				type: 'POST',
				dataType: 'json',
				contentType: 'application/json',
				data: JSON.stringify(items.map(item => item.data)),
			});
			items.forEach(item => item.tr.classList.add('submit-completed'));
		} catch (xhr) {
			handleExcelAjaxError(xhr);
			return false;
		}
	}

	showAlert({
		type: 'success',
		text: 'Registered Successfully',
		callback: reload,
	});
	return true;
}

$(function() {
	const fieldList = excelHeaders.map(item => item.label);
	const requiredList = excelHeaders.filter(item => item.required).map(item => item.label);

	$("#inline-editable").on('click', 'button[value="remove"]', function () {
		this.closest('tr').remove();
	});

	document.getElementById('excelFile').addEventListener('change', function(event) {
		let file = event.target.files[0];
		if (!file) return;

		let allowedExtensions = /(\.xls|\.xlsx)$/i;
		if (!allowedExtensions.exec(file.name)) {
			showAlert({
				type : 'warning',
				text: "엑셀 파일(.xls, .xlsx)만 업로드 가능합니다.",
			});
			event.target.value = ""; // 파일 선택 초기화
			return;
		}

		let reader = new FileReader();
		reader.readAsArrayBuffer(file);

		reader.onload = function(e) {
			let data = new Uint8Array(e.target.result);
			let workbook = XLSX.read(data, { type: 'array' });

			let sheetName = workbook.SheetNames[0]; // 첫 번째 시트만 사용
			let worksheet = workbook.Sheets[sheetName];

			let json = XLSX.utils.sheet_to_json(worksheet, { header: 1 }); // JSON 변환
			if(!json.length) {
				showAlert({
					type: 'error',
					text: '업로드 파일을 확인해주세요.',
				})
				return;
			}

			// header 검사: 1행의 헤더 이름으로 열을 찾는다 (열 순서는 샘플과 달라도 됨)
			const headerRow = (json[0] ?? []).map(item => String(item ?? '').trim());
			const notFields = headerRow.filter(item => item !== '' && !fieldList.includes(item));
			const missingRequired = requiredList.filter(label => !headerRow.includes(label));

			if(notFields.length || missingRequired.length) {
				const wrap = document.createElement('div');
				if(notFields.length) {
					const p = document.createElement('p');
					p.textContent = '허용되지 않는 컬럼이 포함되어있습니다: ' + notFields.join(', ');
					wrap.appendChild(p);
				}
				if(missingRequired.length) {
					const p = document.createElement('p');
					p.textContent = '필수 컬럼이 없습니다: ' + missingRequired.join(', ');
					wrap.appendChild(p);
				}
				showAlert({
					type: 'error',
					html: wrap.innerHTML,
				})
				return;
			}

			// table 초기화
			const table = document.getElementById('inline-editable');
			const colCount = fieldList.length;
			// 표 열(thead 순서 = excelHeaders 순서) → 엑셀 열 번호
			const sourceIndex = fieldList.map(label => headerRow.indexOf(label));

			const itemList = [];
			for(let i = 1; i < json.length; i++) {
				if(!json[i] || json[i].length === 0) continue;
				// 엑셀 행 번호(1행이 헤더)를 함께 보관해 서버 오류를 행에 표시한다
				itemList.push({row: i + 1, values: sourceIndex.map(idx => idx === -1 ? '' : (json[i][idx] ?? ''))});
			}

			// tbody
			$(table).find('tbody').empty();
			let totalRowCount = 0;
			let totalErrorCount = 0;
			const tbody = table.querySelector('tbody');
			for(let i = 0; i < itemList.length; i++) {
				const row = document.createElement('tr');
				row.setAttribute('data-row', itemList[i].row);
				const init = document.createElement('td');
				init.innerText = itemList[i].row;
				row.appendChild(init);
				for(let j = 0; j < colCount; j++) {
					const obj = itemList[i].values;
					const td = document.createElement('td');
					td.innerText = obj[j]??'';

					// required 확인
					if(!td.innerText.length) {
						if(requiredList.includes(fieldList[j])) {
							td.classList.add('edit-required');
							totalErrorCount++;
						}
					}

					row.appendChild(td);
				}
				const last = document.createElement('td');
				const btn = document.createElement('button');
				btn.classList.add(...['btn', 'btn-danger', 'p-1']);
				btn.innerText = '삭제';
				btn.value = 'remove';
				last.appendChild(btn);
				row.appendChild(last);
				tbody.appendChild(row);
				totalRowCount++;
			}

			document.getElementById('totalRowCount').innerText = totalRowCount.toString();
			document.getElementById('totalErrorCount').innerText = totalErrorCount.toString();

			if(totalErrorCount > 0) {
				document.getElementById('excelFormSubmit').setAttribute('disabled', 'disabled');
				document.getElementById('btnErrorFind').removeAttribute('disabled');
			}else{
				document.getElementById('excelFormSubmit').removeAttribute('disabled');
			}

			$("#inline-editable").Tabledit({
				url: false,
				inputClass: 'form-control form-control-sm',
				editButton: false,
				deleteButton: false,
				columns: {
					identifier: [0, "id"],
					editable: getEditableColumns('#inline-editable')
				},
				onSuccess: function (data, textStatus, xhr) {
					console.log(data)
					console.log(textStatus)
					console.log(xhr)
				},
				onSubmit: function(action, serializedData, xhr) {
					console.log("AJAX 요청 차단됨:", action, serializedData);
					if (xhr) xhr.abort(); // 안전하게 AJAX 요청 중단
					return false;
				},
			});

			$('#inline-editable').on('change', 'input.tabledit-input', function() {
				const index = getIndexInParent(this);
				const required = requiredList.includes(fieldList[index-1]);
				const wrap = this.closest('td');
				this.value = this.value.trim();
				if(required) {
					if(!this.value.length) {
						if(!wrap.classList.contains('edit-required')) wrap.classList.add('edit-required');
					}else{
						if(wrap.classList.contains('edit-required')) wrap.classList.remove('edit-required');
					}
				}

				if(!document.querySelectorAll('td.edit-required').length) {
					document.getElementById('excelFormSubmit').removeAttribute('disabled');
					document.getElementById('btnErrorFind').setAttribute('disabled', 'disabled');
				}
			});
		};

		reader.onerror = function(error) {
			console.error("파일 읽기 오류:", error);
		};
	});

	document.getElementById('btnErrorFind').addEventListener('click', function() {
		const errors = document.querySelectorAll('td.edit-required');
		if(!errors.length) return;

		const container = $('#inline-editable').parents('.table-responsive')[0];
		const containerPos = container.getBoundingClientRect();
		const columnPos = errors[0].getBoundingClientRect();
		window.scrollTo(0, columnPos.y - 100);

		setTimeout(function() {
			const scrollX = Math.max(columnPos.x-containerPos.width, 0);
			$(container).animate({ scrollLeft: scrollX }, 500);
		}, 500);
	});

	$('#excelFormSubmit').on('click', async function () {
		const ths = Array.from(document.querySelectorAll('#inline-editable thead th'));
		const editedData = [];

		// 이미 저장된 행(submit-completed)은 다시 보내지 않는다
		$('#inline-editable tbody tr[data-row]:not(.submit-completed)').each(function () {
			const rowData = {_row: Number(this.getAttribute('data-row'))};
			$(this).find('td').each(function (k, v) {
				const field = ths[k] ? ths[k].getAttribute('data-field') : null;
				if(!field) return;
				rowData[field] = $(v).text().trim();
			});
			editedData.push({tr: this, data: rowData});
		});
		if(!editedData.length) return;

		const valid = await validateData(editedData, 50);
		if(valid) await submitData(editedData, 50);
	});

	$('#resetExcelForm').on('click', function() {
		const table = document.getElementById('inline-editable');
		const tbody = $(table).find('tbody')[0];
		$(tbody).empty();
		const tr = document.createElement('tr');
		tr.classList.add('no-result');
		const td = document.createElement('td');
		td.setAttribute('colspan', $(table).find('thead tr th').length);
		td.classList.add('text-center');
		td.innerHTML = '파일을 업로드하세요';
		tr.appendChild(td);
		tbody.appendChild(tr);

		document.getElementById('totalRowCount').innerHTML = 0;
		document.getElementById('totalErrorCount').innerHTML = 0;
		document.getElementById('excelFormSubmit').setAttribute('disabled', 'disabled');
		document.getElementById('btnErrorFind').setAttribute('disabled', 'disabled');
	});
})
