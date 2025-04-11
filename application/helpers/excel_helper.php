<?php
function number_to_alphabet($idx): string
{
    $alphabet_array = range('A', 'Z');
    $alphabet = '';
    $tens_place = ($idx - $idx%26)/26;
    if($tens_place > 0){
        $alphabet .= $alphabet_array[$tens_place-1];
    }
    $ones_place = $idx - 26*$tens_place;
    $alphabet .= $alphabet_array[$ones_place];
    return $alphabet;
}

function alphabet_to_number($alphabet): int
{
    $key_numb = 0;
    $alphabet_array = range('A', 'Z');
    $str_length = strlen($alphabet);
    for($i = 1; $i < $str_length+1; $i++){
        $target_alphabet = substr($alphabet, -$i, 1);
        if(in_array($target_alphabet, $alphabet_array)){
            $key_numb = $key_numb+($i-1)*26+array_search($target_alphabet, $alphabet_array);
        }
    }
    return $key_numb;
}

function next_alphabet($alphabet): string
{
    $current_numb = alphabet_to_number($alphabet);
    return number_to_alphabet($current_numb+1);
}

function get_alphabet_rang($start_alphabet, $end_alphabet): array
{
    $range = array();
    $start_numb = alphabet_to_number($start_alphabet);
    $end_numb = alphabet_to_number($end_alphabet);
    for($i = $start_numb; $i <= $end_numb; $i++) $range[] = number_to_alphabet($i);
    return $range;
}

