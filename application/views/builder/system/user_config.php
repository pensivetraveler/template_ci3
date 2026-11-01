<div class="row g-6 mb-6">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-custom-icon"><?=get_breadcrumbs($titleList)?></ol>
    </nav>
</div>
<div class="row g-6 mb-6">
    <div class="card mb-6">
        <div class="card-body">
            <form id="formUserCfg" onsubmit="return false">
                <h5 class="text-primary fw-bolder">가입 필드</h5>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="signup_email_yn" <?=$signupEmailYn==='Y'?'checked':''?>>
                    <label class="form-check-label" for="signup_email_yn">이메일 사용</label>
                    <div class="form-text">쓰지 않으면 비밀번호 찾기를 쓸 수 없고, 아이디 찾기는 이름과 전화번호로 확인합니다.</div>
                </div>
                <div class="form-check mb-6">
                    <input class="form-check-input" type="checkbox" id="signup_tel_yn" <?=$signupTelYn==='Y'?'checked':''?>>
                    <label class="form-check-label" for="signup_tel_yn">전화번호 사용</label>
                    <div class="form-text">쓰지 않으면 아이디 찾기를 쓸 수 없습니다.</div>
                </div>

                <h5 class="text-primary fw-bolder">비밀번호 찾기 방식</h5>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="password_find_mode" id="password_find_mode_show" value="show" <?=$passwordFindMode==='show'?'checked':''?> <?=$canShowPassword?'':'disabled'?>>
                    <label class="form-check-label" for="password_find_mode_show">비밀번호 표시 (일부 가림)</label>
                    <?php if(!$canShowPassword): ?>
                    <div class="form-text text-danger">복호화할 수 없는 저장 방식이라 고를 수 없습니다.</div>
                    <?php endif; ?>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="password_find_mode" id="password_find_mode_reset" value="reset" <?=$passwordFindMode==='reset'?'checked':''?>>
                    <label class="form-check-label" for="password_find_mode_reset">재설정</label>
                    <div class="form-text">메일 설정이 있으면 재설정 링크를 메일로 보내고, 없으면 확인 후 재설정 화면을 바로 보여 줍니다. 메일 없이는 시스템 관리자 계정을 재설정할 수 없습니다.</div>
                </div>

                <h5 class="text-primary fw-bolder mt-6">로그 보관</h5>
                <div class="mb-6">
                    <label class="form-label" for="log_keep_days">보관 기간(일)</label>
                    <input class="form-control w-px-200" type="number" min="0" id="log_keep_days" value="<?=html_escape($logKeepDays)?>">
                    <div class="form-text">사용자 로그와 요청 로그에 함께 적용합니다. 기간이 지난 로그는 요청을 처리할 때 1,000건씩 나누어 지웁니다. 비우거나 0이면 지우지 않습니다. 법적 보관 의무가 있으면 그 기간 이상으로 정하세요.</div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary" id="btnSaveUserCfg"><?=lang('Submit')?></button>
                </div>
            </form>
        </div>
    </div>
</div>
