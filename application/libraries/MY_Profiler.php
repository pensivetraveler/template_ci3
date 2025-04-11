<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Profiler extends CI_Profiler
{
    protected array $excludes = [
        'config'
    ];

    function run()
    {
        $output = '<div id="codeigniter_profiler" style="clear:both;background-color:#fff;padding:10px;display: none;">';
        $fields_displayed = 0;

        foreach ($this->_available_sections as $section)
        {
            if ($this->{'_compile_'.$section} !== FALSE && !in_array($section, $this->excludes))
            {
                $func = '_compile_'.$section;
                $output .= $this->{$func}();
                $fields_displayed++;
            }
        }

        if ($fields_displayed === 0)
        {
            $output .= '<p style="border:1px solid #5a0099;padding:10px;margin:20px 0;background-color:#eee;">'
                .$this->CI->lang->line('profiler_no_profiles').'</p>';
        }

        $output = $output.'</div>';

//        file_put_contents(FCPATH.'benchmark/profiler.php', $output);return '';
        return $output;
    }

    protected function _compile_memory_usage()
    {
        $output = "\n\n"
            .'<fieldset id="ci_profiler_memory_usage" style="border:1px solid #5a0099;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee;">'
            ."\n"
            .'<legend style="color:#5a0099;">&nbsp;&nbsp;'.$this->CI->lang->line('profiler_memory_usage')."&nbsp;&nbsp;</legend>\n";
        if(($usage = memory_get_usage()) != '') {
            $output .= '<div style="color:#5a0099;font-weight:normal;padding:4px 0 4px 0;">'.number_format($usage).' bytes</div>';
            $output .= '<div style="color:#5a0099;font-weight:normal;padding:4px 0 4px 0;">'.number_format(memory_get_peak_usage()).' bytes (peak)</div>';
        }else{
            $output .= '<div style="color:#5a0099;font-weight:normal;padding:4px 0 4px 0;">'.$this->CI->lang->line('profiler_no_memory').'</div>';
        }
        $output .= '</fieldset>';
        return $output;
    }
}