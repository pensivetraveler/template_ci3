$(function() {
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
})
