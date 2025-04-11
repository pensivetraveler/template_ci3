$(function() {
    // popover
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

	// perfect scroll bar
	$('.ps').each(function(k,v) {
		new PerfectScrollbar(v, {
			wheelPropagation: false
		});
	})

	// spinner
	if(document.getElementById('loader') !== null)
		document.getElementById('loader').classList.add('d-none');
})
