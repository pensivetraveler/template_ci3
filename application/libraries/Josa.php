<?php defined('BASEPATH') OR exit('No direct script access allowed');

// 참조
// http://taegon.kim/archives/4776
// https://www.phpbb.de/infos/3.1/xref/nav.html?_functions/index.html
// http://zetawiki.com/wiki/UTF-8_%ED%95%9C%EA%B8%80_%EC%B4%88%EC%84%B1,_%EC%A4%91%EC%84%B1,_%EC%A2%85%EC%84%B1_%EB%B6%84%EB%A6%AC_(PHP)

class Josa
{
    // 조사 리스트
    // 0,2,4,6 = 종성있음
    // 1,3,5,7 = 종성없음
    const POSTPOSITION_LIST = '이가은는을를과와';

    // 인스턴스
    static $instance = null;

    // 문장에서 조사를 적절하게 변환
    public static function conv($str)
    {
        return self::getInstance()->__conv($str);
    }

    // 인스턴스 반환
    static function getInstance()
    {
        if (self::$instance === null) self::$instance = new self;

        return self::$instance;
    }

    // 생성자. internal encoding 세팅
    function __construct()
    {
        mb_internal_encoding('UTF-8');
    }

    // 조사 변환
    function __conv($str)
    {
        return preg_replace_callback("/(.)\\{([".self::POSTPOSITION_LIST."])\\}/u", array($this, 'replace'), $str);
    }

    // preg_replace_callback의 callback함수
    function replace($matches)
    {
        // 조사 바로 앞 한글자
        $lastChar = $matches[1];
        // 조사
        $postpositionMatched = $matches[2];

        // 종성 유/무에 따른 조사배열 세팅
        $arrPostposition = array(
            '종성없음' => $postpositionMatched,
            '종성있음' => $postpositionMatched
        );
        $pos = mb_strpos(self::POSTPOSITION_LIST, $postpositionMatched);
        if ($pos % 2 != 0) {
            $arrPostposition['종성있음'] = mb_substr(self::POSTPOSITION_LIST, $pos-1, 1);
        } else {
            $arrPostposition['종성없음'] = mb_substr(self::POSTPOSITION_LIST, $pos+1, 1);
        }

        // 기본값 = '종성있음'
        $lastCharStatus = '종성있음';

        // 2바이트 이상 유니코드 문자
        if (strlen($lastChar) > 1) {
            $code = $this->utf8Ord($lastChar) - 44032;

            // 한글일 경우 (가=0, 힣=11171)
            if ($code > -1 && $code < 11172) {
                // 초성
                //$code / 588
                // 중성
                //$code % 588 / 28
                // 종성
                if ($code % 28 == 0) $lastCharStatus = '종성없음';
            }
            // 1바이트 ASCII
        } else {
            // 숫자중 2(이),4(사),5(오),9(구)는 종성이 없음
            if (strpos('2459', $lastChar) > -1) {
                $lastCharStatus = '종성없음';
            }
        }

        // 종성 상태에 알맞는 조사를 붙여 반환
        return $lastChar.$arrPostposition[$lastCharStatus];
    }

    // ord() UTF-8 버전
    function utf8Ord($char)
    {
        switch (strlen($char)) {
            case 1:
                return ord($char);
                break;
            case 2:
                return ((ord($char[0]) & 0x1F) << 6) | (ord($char[1]) & 0x3F);
                break;
            case 3:
                return ((ord($char[0]) & 0x0F) << 12) | ((ord($char[1]) & 0x3F) << 6) | (ord($char[2]) & 0x3F);
                break;
            case 4:
                return ((ord($char[0]) & 0x07) << 18) | ((ord($char[1]) & 0x3F) << 12) | ((ord($char[2]) & 0x3F) << 6) | (ord($char[3]) & 0x3F);
                break;
            default:
                return $char;
        }
    }
}