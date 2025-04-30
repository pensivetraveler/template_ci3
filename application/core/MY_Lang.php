<?php

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014-2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @copyright	Copyright (c) 2014-2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Language Class extension.
 *
 * Adds language fallback handling.
 *
 * When loading a language file, CodeIgniter will load first the english version,
 * if appropriate, and then the one appropriate to the language you specify.
 * This lets you define only the language settings that you wish to over-ride
 * in your idiom-specific files.
 *
 * This has the added benefit of the language facility not breaking if a new
 * language setting is added to the built-in ones (english), but not yet
 * provided for in one of the translations.
 *
 * To use this capability, transparently, copy this file (MY_Lang.php)
 * into your application/core folder.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Language
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/libraries/language.html
 */
class MY_Lang extends CI_Lang {

    /**
     * Refactor: base language provided inside system/language
     *
     * @var string
     */
    public $base_language = 'english';

    /**
     * Class constructor
     *
     * @return	void
     */
    public function __construct()
    {
        parent::__construct();
    }

    // --------------------------------------------------------------------

    /**
     * Load a language file, with fallback to english.
     *
     * @param	mixed	$langfile	Language file name
     * @param	string	$idiom		Language name (english, etc.)
     * @param	bool	$return		Whether to return the loaded array of translations
     * @param 	bool	$add_suffix	Whether to add suffix to $langfile
     * @param 	string	$alt_path	Alternative path to look for the language file
     *
     * @return	void|string[]	Array containing translations, if $return is set to TRUE
     */
    public function load($langfile, $idiom = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '')
    {
        if (is_array($langfile))
        {
            foreach ($langfile as $value)
            {
                $this->load($value, $idiom, $return, $add_suffix, $alt_path);
            }

            return;
        }

        $langfile = str_replace('.php', '', $langfile);

        if ($add_suffix === TRUE)
        {
            $langfile = preg_replace('/_lang$/', '', $langfile) . '_lang';
        }

        $langfile .= '.php';

        if (empty($idiom) OR ! preg_match('/^[a-z_-]+$/i', $idiom))
        {
            $config = & get_config();
            $idiom = empty($config['language']) ? $this->base_language : $config['language'];
        }

        if ($return === FALSE && isset($this->is_loaded[$langfile]) && $this->is_loaded[$langfile] === $idiom)
        {
            return;
        }

        // load the default language first, if necessary
        // only do this for the language files under system/
        $basepath = SYSDIR . DIRECTORY_SEPARATOR . 'language/' . $this->base_language . '/' . $langfile;
        if (($found = file_exists($basepath)) === TRUE)
        {
            include($basepath);
        }

        // Load the base file, so any others found can override it
        $basepath = BASEPATH . 'language/' . $idiom . '/' . $langfile;
        if (($found = file_exists($basepath)) === TRUE)
        {
            include($basepath);
        }

        // Do we have an alternative path to look in?
        if ($alt_path !== '')
        {
            $alt_path .= 'language/' . $idiom . '/' . $langfile;
            if (file_exists($alt_path))
            {
                include($alt_path);
                $found = TRUE;
            }
        } else
        {
            foreach (get_instance()->load->get_package_paths(TRUE) as $package_path)
            {
                $package_path .= 'language/' . $idiom . '/' . $langfile;
                if ($basepath !== $package_path && file_exists($package_path))
                {
                    include($package_path);
                    $found = TRUE;
                    break;
                }
            }
        }

        if ($found !== TRUE)
        {
            show_error('Unable to load the requested language file: language/' . $idiom . '/' . $langfile);
        }

        if (!isset($lang) OR ! is_array($lang))
        {
            log_message('error', 'Language file contains no data: language/' . $idiom . '/' . $langfile);

            if ($return === true)
            {
                return array();
            }
            return;
        }

        if ($return === true)
        {
            return $lang;
        }

        $this->is_loaded[$langfile] = $idiom;
        $this->language = array_merge($this->language, $lang);

        log_message('info', 'Language file loaded: language/' . $idiom . '/' . $langfile);
        return true;
    }

    public function line($line, $log_errors = true)
    {
        if(is_array($line)) {
            if(is_list_type($line)) {
                return array_reduce($line, function($carry, $item) use ($log_errors) {
                    $carry .= $this->line($item, $log_errors);
                    return $carry;
                }, '');
            }else{
                return $this->line_arr($line, $log_errors);
            }
        }

        if(strpos($line, 'lang:') !== false) $line = str_replace('lang:', '', $line);
        $value = $this->line_exists($line, $this->language, $log_errors);

        return $value === false?$line:$value;
    }

    public function status($code)
    {
        $value = $this->line_exists('status_code.'.$code, $this->language);
        if(!$value && (int)$code % 10 === 0) {
            $code = strval((int)$code/10);
            $value = $this->line_exists('status_code.'.$code, $this->language);
        }
        return $value === false?$code:$value;
    }

    public function line_exists($line, $languages = [], $log_errors = true)
    {
        if(empty($languages)) $languages = $this->language;

        $CI =& get_instance();

        $value = $this->find_line($line, $languages);

        if($value === false) {
            if($CI->config->config['language'] !== $this->base_language) {
                $alternates = [];
                foreach ($this->is_loaded as $langfile=>$language) {
                    if($language !== $this->base_language) $alternates = array_merge($alternates, $this->load($langfile, $this->base_language, true));
                }
                $value = $this->find_line($line, $alternates);
            }
        }

        // Because killer robots like unicorns!
        if ($value === false && $log_errors === true)
        {
            log_message('error', "Could not find the language line '$line' in {$this->base_language}");
        }

        return $value;
    }

    protected function find_line($line, $languages)
    {
        if(!str_contains($line, '.') || str_ends_with($line, '.')){
            $value = $languages[$line] ?? false;
        }else{
            $keys = explode('.', $line);

            $value = $languages;
            foreach ($keys as $key) {
                if(isset($value[$key])) {
                    $value = $value[$key];
                }else{
                    $value = false;
                    break;
                }
            }

            if($value === false) {
                $keys[0] = 'common';
                foreach ($keys as $key) {
                    if(isset($value[$key])) {
                        $value = $value[$key];
                    }else{
                        $value = false;
                        break;
                    }
                }
            }
        }
        return $value;
    }

    protected function line_arr($data, $log_errors = true): string
    {
        $line = $data['line'];
        $replace = $data['replace'] ?? [];

        return $this->nline($line, $replace, $log_errors);
    }

    public function nline($line, $replace = '', $log_errors = true): string
    {
        $line = $this->line($line, $log_errors);
        $count = substr_count($line, '%s');

        if($count > 0) {
            if(is_array($replace)) {
                $replace = array_slice($replace, 0, $count);
                if($count > count($replace)) {
                    $start_index = count($replace);
                    $fill_count = $count - count($replace);
                    $replace = array_merge($replace, array_fill($start_index, $fill_count, '%s'));
                }
                $line = vsprintf($line, $replace);
            }else{
                $line = sprintf($line, $replace);
            }
        }

        return $line;
    }

    public function line_icon($line, $icon_type = '', $icon = true, $log_errors = true):string
    {
        $value = $this->nline($line, strtoupper($icon_type), $log_errors);
        if($icon) $value = get_icon_by_type($icon_type)."<span class='ms-1'>$value</span>";
        return $value;
    }
}

// --------------------------------------------------------------------
// The method below was used with phpunit to ensure correctness of the above.
//	public function test_fallback()
//	{
//		// system target language file
//		$this->ci_vfs_create('system/language/martian/number_lang.php', "<?php \$lang['fruit'] = 'Apfel';");
//		$this->assertTrue($this->lang->load('number', 'martian'));
//		$this->assertEquals('Apfel', $this->lang->language['fruit']);
//		$this->assertEquals('Bytes', $this->lang->language['bytes']);
//
//		// application target language file
//		$this->ci_vfs_create('application/language/klingon/number_lang.php', "<?php \$lang['fruit'] = 'Apfel';");
//		$this->assertTrue($this->lang->load('number', 'klingon'));
//		$this->assertEquals('Apfel', $this->lang->language['fruit']);
//		$this->assertEquals('Bytes', $this->lang->language['bytes']);
//
//		// both system & application language files
//		$this->ci_vfs_create('system/language/romulan/number_lang.php', "<?php \$lang['apple'] = 'Core';");
//		$this->ci_vfs_create('application/language/romulan/number_lang.php', "<?php \$lang['fruit'] = 'Cherry';");
//		$this->assertTrue($this->lang->load('number', 'romulan'));
//		$this->assertEquals('Cherry', $this->lang->language['fruit']);
//		$this->assertEquals('Bytes', $this->lang->language['bytes']);
//		$this->assertEquals('Core', $this->lang->language['apple']);
//	}
