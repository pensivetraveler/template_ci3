function checkAgree() {
	if(document.getElementById('termsofuse').checked && document.getElementById('privacy').checked){
		location.href = '/auth/signup';
	}else{
		alert('약관에 동의해주세요.')
	}
}
