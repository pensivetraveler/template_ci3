function emitErrorMessage(response) {
	let key = Object.keys(response.data)[0];
	let targetData = response.data[key];

	let errorCount = 0;
	const index = getNodeIndexInParent(document.querySelector(`th[data-field="${key}"]`));
	if(index !== -1) {
		for(const td of document.querySelectorAll(`#inline-editable tbody tr:not(.submit-completed) td:nth-child(${index+1})`)) {
			if(td.querySelector('span').innerHTML === targetData) {
				td.classList.add('edit-required');
				errorCount++;
			}
		}

		document.getElementById('totalErrorCount').innerText = errorCount.toString();
		document.getElementById('btnErrorFind').removeAttribute('disabled');
		document.getElementById('excelFormSubmit').setAttribute('disabled', 'disabled');
	}

	let filtered = excelHeaders.filter(item => item.field === key);
	if(filtered.length) key = filtered[0].label;

	showAlert({
		type : 'error',
		html: `<p>${response.msg}</p><p class="text-danger mt-2 mb-0">(${key} : ${targetData})</p>`,
	});
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
	$.ajax({
		url: common.API_URI + '/deleteExcelFile',
		type: "PATCH",
		data: {
			class: document.body.getAttribute('data-class'),
		},
		dataType: 'json',
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
	let index = 0;
	let valid = true;

	while (index < dataArray.length) {
		if (!valid) break; // 에러 발생 시 중단

		let chunk = dataArray.slice(index, index + chunkSize);
		index += chunkSize;

		try {
			const response = await $.ajax({
				url: common.API_URI + '/excelValidate',
				type: "POST",
				data: JSON.stringify(chunk),
				contentType: "application/json",
			});

			console.log("Chunk sent successfully");
		} catch (xhr) {
			console.log(xhr.responseJSON?.data || "Unknown error response");

			if (xhr.responseJSON && [404, 409].includes(Math.floor(xhr.responseJSON.code / 10))) {
				emitErrorMessage(xhr.responseJSON);
			} else {
				showAlert({
					type: "error",
					text: xhr.responseJSON ? xhr.responseJSON.msg : "Unknown Error",
				});
			}

			valid = false;
			break; // 에러 발생 시 중단
		}
	}

	return valid;
}

async function submitData(dataArray, chunkSize = 50) {
	let index = 0;
	let valid = true;

	while (index < dataArray.length) {
		if (!valid) break;

		let chunk = dataArray.slice(index, index + chunkSize);
		index += chunkSize;

		try {
			const response = await $.ajax({
				url: common.API_URI + '/excelUpload',
				type: 'POST',
				dataType: 'json',
				contentType: 'application/json', // content-type 지정
				data: JSON.stringify(chunk),
			});

			console.log(response);

			// 성공적으로 처리된 row에 class 추가
			document.querySelectorAll('#inline-editable tbody tr').forEach(tr => {
				if (parseInt(tr.id) > index - chunkSize && parseInt(tr.id) <= index) {
					tr.classList.add('submit-completed');
				}
			});

		} catch (xhr) {
			valid = false; // 에러 발생 시 중단

			if (xhr.responseJSON && [404, 409].includes(Math.floor(xhr.responseJSON.code / 10))) {
				emitErrorMessage(xhr.responseJSON);
			} else {
				showAlert({
					type: 'error',
					text: xhr.responseJSON ? xhr.responseJSON.msg : 'Unknown Error',
				});
			}
		}
	}

	if (valid) {
		showAlert({
			type: 'success',
			text: 'Registered Successfully',
			callback: reload,
		});
	}

	return valid;
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

			// header 검사
			const notFields = [];
			for(const item of json[0]) if(!fieldList.includes(item)) notFields.push(item);

			if(notFields.length) {
				showAlert({
					type: 'error',
					html: "허용되지 않는 컬럼이 포함되어있습니다.<br><span class='small mt-2'>"+notFields.join(', ')+"</span>",
				})
				return;
			}

			// table 초기화
			const table = document.getElementById('inline-editable');
			const colCount = json[0].length;

			const itemList = [];
			for(let i = 1; i < json.length; i++) {
				if(json[i].length === 0) continue;
				itemList.push(json[i]);
			}

			// tbody
			$(table).find('tbody').empty();
			let totalRowCount = 0;
			let totalErrorCount = 0;
			const tbody = table.querySelector('tbody');
			for(let i = 0; i < itemList.length; i++) {
				const row = document.createElement('tr');
				const init = document.createElement('td');
				init.innerText = i+1;
				row.appendChild(init);
				for(let j = 0; j < colCount; j++) {
					const obj = itemList[i];
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
				const index = getNodeIndexInParent(this);
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
		let editedData = [];

		$('#inline-editable tbody tr:not(.submit-completed)').each(function () {
			let row = $(this);
			let rowData = {};
			$(row).find('td').each(function (k, v) {
				if (k === 0 || k === $(row).find('td').length - 1) return;
				const columnName = excelHeaders[k - 1].field;
				rowData[columnName] = $(v).text();
			});
			editedData.push(rowData);
		});

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
		td.setAttribute('colspan', $(table).find('thead tr th').length+2);
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
