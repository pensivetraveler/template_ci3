function setViewListItemFile(item) {
	const fullItem = JSON.stringify(item).replace(/"/g, "'");
	return `
        <div class="form-list-item d-flex align-items-center" data-full-item="${fullItem}">
            <div class="badge text-body text-truncate">
                <a href="${common.CURRENT_URI}/downloader/${item.file_id}">
                    <i class="ri-file-download-fill ri-16px align-middle"></i>
                    <span class="h6 mb-0 align-middle">${item.orig_name}</span>
                </a>
            </div>
        </div>
    `;

}

function applyViewData(dataId) {
	const data = getData(dataId);
	const container = document.getElementById('view-container');
	if(!container) return;

	for(const key of Object.keys(data)){
		if(appPlugins.view !== null && appPlugins.view.hasOwnProperty(key)) {
			console.log(key)
		}else{
			if(container.querySelector(`#${key}`) === null) continue;
			switch (key) {
				case 'thumbnail' :
					if(data[key] !== null && data[key].length && data[key][0].file_link !== null) {
						container.querySelector(`#${key}`).style.backgroundImage = `url(${data[key][0].file_link})`
					}else{
						container.querySelector(`#${key}`).classList.add('none');
					}
					break;
				case 'uploads' :
					let html = '';
					if(data[key] !== null && data[key].length) {
						let list = [];
						if(!isEmpty(data[key])) {
							isArray(data[key]) ? list = data[key] : list.push(data[key]);
							list.forEach((item) => {
								html += setViewListItemFile(item);
							});
							container.querySelector(`#${key}`).classList.remove('d-none');
						}
					}
					document.querySelector(`#${key}`).innerHTML = html;
					break;
				default :
					if(data[key]) {
						container.querySelector(`#${key}`).innerHTML = data[key];
					}else{
						container.querySelector(`#${key}`).classList.add('no-value')
					}
			}
		}
	}

	document.body.setAttribute('data-onload', true);
}

function setButtonsDisplay() {
	const isMine = isMyData(common.KEY, false);
	if(!isMine) {
		$('.btn-view-edit').remove();
		$('.btn-view-delete').remove();
	}
}

$(function() {
	applyViewData(common.KEY);

	setButtonsDisplay();

	$('.btn-view-list').on('click', function(e) {
		location.href = common.PAGE_LIST_URI;
	});

	$('.btn-view-edit').on('click', function(e) {
		if(common.PAGE_EDIT_URI) location.href = common.PAGE_EDIT_URI + '/' + common.KEY;
	});

	$('.btn-view-delete').on('click', function(e) {
		if(!common.KEY) throw new Error(`KEY is not defined`);
		deleteData(common.KEY, {
			callback: redirect,
			params: common.PAGE_LIST_URI,
		});
	});
});
