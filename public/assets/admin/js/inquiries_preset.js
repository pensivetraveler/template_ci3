appPlugins.list.datatable = {
	rowCallback : function(row, data, displayNum, displayIndex, dataIndex) {
		if(data.reply_yn) $(row).css('background-color', '#cbcbcb');
	}
}
