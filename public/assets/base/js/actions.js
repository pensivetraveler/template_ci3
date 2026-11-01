// CSP 에서 인라인 on* 속성과 javascript: 링크를 쓰지 않도록 클릭을 한곳에서 받는다.
// <button data-action="이름"> → pageActions 의 함수를 요소와 함께 부른다. 프로젝트 화면은 window.pageActions 에 함수를 더한다
window.pageActions = {
    'history-back': () => history.back(),
    'go': (el) => { location.href = el.dataset.href; },
    'fold-parent': (el) => foldDaumPostcode(el.parentElement),
    'find-address': (el) => findAddress(document.getElementById(el.dataset.target)),
    'check-duplicate': (el) => checkDuplicate(el),
    'download-file': (el) => downloadFile(Number(el.dataset.fileId)),
    'delete-file': (el) => deleteFile(el, el.dataset.fileType),
    'logout': () => logout(),
    'reset-excel-file': () => resetExcelFile(),
    'check-domain-valid': (el) => checkDomainValid(el),
    'check-domain-register': (el) => el.closest('.domain-register-init').__domainRegister.checkDomain(),
};

document.addEventListener('click', function (event) {
    if (event.target.closest('a[href="#"]')) event.preventDefault();

    const el = event.target.closest('[data-action]');
    if (!el || el.disabled) return;
    const action = window.pageActions[el.dataset.action];
    if (typeof action === 'function') action(el, event);
});

// 폼은 스크립트(submitAjax)로만 보낸다
document.addEventListener('submit', function (event) {
    if (event.target.matches('form[data-no-submit]')) event.preventDefault();
});
