<?php
// --------------------------------------------------------------------

if ( ! function_exists('snakeize'))
{
	/**
	 * Snakeize
	 *
	 * Reverse camelized string to snakeized
	 *
	 * @param	string	$str	Input string
	 * @return	string
	 */
	function snakeize($str)
	{
		return strtolower(preg_replace('/[A-Z]/', '_$0', $str));
	}
}

// --------------------------------------------------------------------

if ( ! function_exists('percentize'))
{
	/**
	 * Percentize
	 *
	 * Takes number to percent
	 *
	 * @param	Number	$numerator	    Input Number
	 * @param	Number	$denominator	Input Number
	 * @return	string
	 */
	function percentize($numerator, $denominator = 100)
	{
		$fraction = (int)$numerator/(int)$denominator;
		$formatter = new NumberFormatter('en_US', NumberFormatter::PERCENT);
		return $formatter->format($fraction);
	}
}


// --------------------------------------------------------------------

if ( ! function_exists('get_language_code'))
{
	function get_language_code($language): string
	{
		$languages = [
			'english' => 'en',
			'korean' => 'ko',
			'japanese' => 'ja',
			'chinese' => 'zh',
			'french' => 'fr',
			'german' => 'de',
			'spanish' => 'es',
			'italian' => 'it',
			'russian' => 'ru',
			'portuguese' => 'pt',
			'arabic' => 'ar',
			'hindi' => 'hi'
		];

		$language = strtolower($language);
		return $languages[$language] ?? 'en';
	}
}

if( ! function_exists('literal_to_entity'))
{
    /**
     * 리터럴 문자열 안의 특수 문자와 공백, underscore(_), hyphen(-)을
     * 대응하는 HTML 엔티티로 변환합니다.
     *
     * @param string $str
     * @return string
     */
    function literal_to_entity(string $str): string
    {
        $map = [
            '&' => '&amp;',
            '<' => '&lt;',
            '>' => '&gt;',
            '"' => '&quot;',
            "'" => '&#39;',
            ' ' => '&nbsp;',
            '_' => '&#95;',
            '-' => '&#45;',
        ];

        // strtr을 쓰면 한 번에 치환할 수 있습니다
        return strtr($str, $map);
    }
}

if( ! function_exists('entity_to_literal'))
{
    /**
     * HTML 엔티티(이름형, 숫자형)를 실제 리터럴 문자로 디코딩합니다.
     *
     * @param string $str
     * @return string
     */
    function entity_to_literal(string $str): string
    {
        // ENT_QUOTES: ' " 모두, ENT_HTML5: HTML5 엔티티 지원
        $decoded = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // &nbsp; → U+00A0(non-breaking space)로 디코딩되는데,
        // 이를 일반 공백(U+0020)로 바꿔 주고 싶다면 아래도 추가합니다.
        return str_replace("\xC2\xA0", ' ', $decoded);
    }
}