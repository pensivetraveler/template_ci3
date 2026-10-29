// 게시글 본문은 저장 때 서버가 정화한 HTML(sanitize_rich_html)이라 HTML 로 보여 준다 (기본 상세 표시는 textContent)
appPlugins.view = appPlugins.view || {};
appPlugins.view.content = function (element, value) {
	element.innerHTML = value || '';
};
