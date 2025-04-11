<?php
// --------------------------------------------------------------------

if ( ! function_exists('uncamelize'))
{
	/**
	 * Uncamelize
	 *
	 * Reverse camelized string to underscore
	 *
	 * @param	string	$str	Input string
	 * @return	string
	 */
	function uncamelize($str)
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
