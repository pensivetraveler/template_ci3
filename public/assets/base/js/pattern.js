if(typeof specialChars === 'undefined') var specialChars = '[\\{\\}\\[\\]\\/?.,;:|\\)*~`!^\\-_+┼<>@\\#$%&\'\"\\\\\(\\=]';
if(typeof exceptionalSpecialCharList === 'undefined') var exceptionalSpecialCharList = ['#', '&', '*', ',', '\\-', '\\/'];

/**************************************************************/

function getRegExp(type) {
    switch (type) {
        case 'numeric' :
            return /^\d+$/;
        case 'alpha_numeric' :
            return /^[a-zA-Z0-9]+$/;
        case 'alpha_numeric_spaces' :
            return /^[a-zA-Z0-9 ]+$/;
        case 'alpha_dash' :
            return /^-?\d+$/;
        case 'decimal' :
            return /^-?\d+(\.\d+)?$/;
        case 'is_natural' :
            return /^\d+$/;
        case 'is_natural_no_zero' :
            return /^[1-9]\d*$/;
        default :
            console.error('getRegExp : type is not matched.');
            return;
    }
}

function getValueWithoutExcepts(selector, option) {
    let value = document.querySelector(selector).value;
    if(option.isSpecialChar){
        for(let exception of exceptionalSpecialCharList) {
            const reg = new RegExp('['+exception+']', 'g');
            value = value.replace(reg, '');
        }
    }
    return value;
}

function getEscapedPattern(pattern) {
    return pattern
        .replace(/\\/g, '\\\\')     // 역슬래시를 이스케이프
        .replace(/\[/g, '\\[')      // 여는 대괄호를 이스케이프
        .replace(/\]/g, '\\]');     // 닫는 대괄호를 이스케이프
}

/**************************************************************/

function checkEmpty(selector, title) {
    const value = document.querySelector(selector).value.trim();
    if(value === '') {
        alert(`${title}을/를 입력하세요.`)
        document.querySelector(selector).focus();
        return false;
    }
    return true;
}

function checkSpecial(selector, title) {
    const value = document.querySelector(selector).value.trim();
    if(isSpecialChar(value)) {
        const exceptionalSpecialCharText = exceptionalSpecialCharList.join(' ').replace(/\\/g, '')
        alert(`${title}에 특수기호는 ${exceptionalSpecialCharText} 만 허용됩니다.`)
        document.querySelector(selector).focus();
        return false;
    }
    return true;
}

function checkKor(selector, title, option) {
    if(!option.isEmpty && !checkEmpty(selector, title)) return false;
    if(option.isSpecialChar && !checkSpecial(selector, title)) return false;

    const value = getValueWithoutExcepts(selector, option);
    if(isKor(value, option)) {
        if(option.isSpecialChar){
            const exceptionalSpecialCharText = exceptionalSpecialCharList.join(' ').replace(/\\/g, '')
            alert(`${title}에 한글 및 특수기호 ${exceptionalSpecialCharText} 만 입력 가능합니다.`);
        }else{
            alert(`${title}에 한글만 입력 가능합니다.`);
        }
        document.querySelector(selector).focus();
        return false;
    }
    return true;
}

function checkEng(selector, title, option) {
    if(!option.isEmpty && !checkEmpty(selector, title)) return false;
    if(option.isSpecialChar && !checkSpecial(selector, title)) return false;

    const value = getValueWithoutExcepts(selector, option);
    if(isEng(value, option)) {
        if(option.isSpecialChar){
            const exceptionalSpecialCharText = exceptionalSpecialCharList.join(' ').replace(/\\/g, '')
            alert(`${title}에 영문 및 특수기호 ${exceptionalSpecialCharText} 만 입력 가능합니다.`);
        }else{
            alert(`${title}에 영문만 입력 가능합니다.`);
        }
        document.querySelector(selector).focus();
        return false;
    }
    return true;
}

function checkNum(selector, title, option) {
    if(!option.isEmpty && !checkEmpty(selector, title)) return false;
    if(option.isSpecialChar && !checkSpecial(selector, title)) return false;

    const value = getValueWithoutExcepts(selector, option);
    if(isNum(value, option)) {
        if(option.isSpecialChar){
            const exceptionalSpecialCharText = exceptionalSpecialCharList.join(' ').replace(/\\/g, '')
            alert(`${title}에 숫자 및 특수기호 ${exceptionalSpecialCharText} 만 입력 가능합니다.`);
        }else{
            alert(`${title}에 숫자만 입력 가능합니다.`);
        }
        document.querySelector(selector).focus();
        return false;
    }
    return true;
}

function checkEngNum(selector, title, option) {
    if(!option.isEmpty && !checkEmpty(selector, title)) return false;
    if(option.isSpecialChar && !checkSpecial(selector, title)) return false;

    const value = getValueWithoutExcepts(selector, option);
    if(isEngNum(value, option)) {
        if(option.isSpecialChar){
            const exceptionalSpecialCharText = exceptionalSpecialCharList.join(' ').replace(/\\/g, '')
            alert(`${title}에 영문, 숫자 및 특수기호 ${exceptionalSpecialCharText} 만 입력 가능합니다.`);
        }else{
            alert(`${title}에 영문, 숫자만 입력 가능합니다.`);
        }
        document.querySelector(selector).focus();
        return false;
    }
    return true;
}

function checkRatin(id, message) {
    var nonLatinExtendedRegex = /[\u00C0-\u00FF]/;
    if(nonLatinExtendedRegex.test($("#" + id).val())){
        alert(message + " 영문, 숫자, #, &만 입력 가능합니다.");
        $("#" + id).focus();
        return false;
    }
    return true;
}

function checkPattern(text, pattern, option) {
    if(!option.isSpace) pattern = pattern.replace('\\s', '');
    if(option.isNot) pattern = pattern.substring(0, pattern.indexOf('[')+1)+'^'+pattern.substring(pattern.indexOf('[')+1, pattern.length);
    const regexp = new RegExp(pattern, 'ig');
    return regexp.test(text);
}

/**************************************************************/

function isExist(element){
    if(element !== null && typeof element !== "undefined"){
        return true;
    }else{
        return false;
    }
}

function isEmpty(data) {
    if(data === null) return true;
    if(typeof data === 'undefined' || typeof data === 'function') return true;
    if(typeof data === 'number') return false;
    if(isObject(data)) return Object.keys(data).length === 0;
    if(isArray(data)) return data.length === 0;
    return (!data.trim() || 0 === data.length);
}

String.prototype.isEmpty = function() {
    return (this.length === 0 || !this.trim());
};

function isKor(text, option) {
    const pattern = '[가-힣ㄱ-ㅎㅏ-ㅣ\\s]';
    return checkPattern(text, pattern, option);
}

function isEng(text, option) {
    const pattern = '[a-zA-Z\\s]';
    return checkPattern(text, pattern, option);
}

function isNum(text, option) {
    const pattern = '[0-9\\s]';
    return checkPattern(text, pattern, option);
}

function isEngNum(text, option) {
    const pattern = '[a-zA-Z0-9\\s]';
    return checkPattern(text, pattern, option);
}

function isLatin(text) {
    const latin = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏàáâãäåæçèéêëìíîïÐÑÒÓÔÕÖØÙÚÛÜÝÞßðñòóôõöøùúûüýþÿ';
}

function isSpecialChar(text) {
    let pattern = specialChars;
    for(const exception of exceptionalSpecialCharList) {
        pattern = pattern.replace(exception, '');
    }
    const regexp = new RegExp(pattern, 'ig');
    return regexp.test(text);
}

function isMobile(){
    var UserAgent = navigator.userAgent;
    if (UserAgent.match(/iPhone|iPod|Android|Windows CE|BlackBerry|Symbian|Windows Phone|webOS|Opera Mini|Opera Mobi|POLARIS|IEMobile|lgtelecom|nokia|SonyEricsson/i) != null || UserAgent.match(/LG|SAMSUNG|Samsung/) != null)
    {
        return true;
    }else{
        return false;
    }
}

function isNumeric(str) {
    if (typeof str != "string") return false // we only process strings!
    return !isNaN(str) && // use type coercion to parse the _entirety_ of the string (`parseFloat` alone does not do this)...
        !isNaN(parseFloat(str)) // ...and ensure strings of whitespace fail
}

function isInteger(value) {
    if(parseInt(value,10).toString()===value) {
        return true
    }
    return false;
}

function isObject(data) {
    return typeof data === 'object' && !Array.isArray(data) && data !== null;
}

function isArray(data) {
    return Array.isArray(data);
}
