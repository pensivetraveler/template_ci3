<?php
// $_FILES 에 $field 에 해당하는 인자가 존재하는지, 그리고 업로드가 되었는지 확인
function is_file_posted($field)
{
	$_file = null;

	// Is $_FILES[$field] set? If not, no reason to continue.
	if (isset($_FILES[$field]))
	{
		$_file = $_FILES[$field];
	}
	// Does the field name contain array notation?
	elseif (($c = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $field, $matches)) > 1)
	{
		$_file = $_FILES;
		for ($i = 0; $i < $c; $i++)
		{
			// We can't track numeric iterations, only full field names are accepted
			if (($field = trim($matches[0][$i], '[]')) === '' OR ! isset($_file[$field]))
			{
				$_file = NULL;
				break;
			}

			$_file = $_file[$field];
		}
	}

	if ( ! isset($_file))
	{
		return false;
	}

	if(gettype($_file['name']) === 'array') {
		foreach ($_file['error'] as $error) {
			if ($error === UPLOAD_ERR_NO_FILE) {
				return false; // No file was uploaded
			}

			if ($error !== UPLOAD_ERR_OK) {
				return false; // There was an error during file upload
			}
		}

		foreach ($_file['size'] as $size) {
			if ($size === 0) {
				return false; // File is empty
			}
		}
	}else{
		// Check if the file is actually uploaded
		if ($_file['error'] === UPLOAD_ERR_NO_FILE) {
			return false; // No file was uploaded
		}

		// Check for other upload errors
		if ($_file['error'] !== UPLOAD_ERR_OK) {
			return false; // There was an error during file upload
		}

		// Check if the file is empty
		if ($_file['size'] === 0) {
			return false; // File is empty
		}
	}

	return true;
}

// 확장자 추출
function get_file_ext($filename)
{
	return substr($filename,strrpos($filename,".")+1);
}

function get_file_size_unit($filesize)
{
	if(is_string($filesize)) $filesize = (int)$filesize;
	$return = '';
	$units = ['g','m','k'];
	foreach ($units as $i=>$unit) {
		$multiplier = 3-$i;
		$divisor = pow(1024, $multiplier);
		$result = floor($filesize/$divisor);
		if($result > 0) {
			$return = $unit;
			break;
		}
	}
	return $return;
}

// byte 단위의 파일크기를 KB로 변환
// 크기가 1MB 이상이 되면 소수점을 생략하고
// 그이하일 경우는 소수점 둘째자리까지 출력한다.
function get_file_size_to_kb($filesize)
{
	$int_temp = "";
	$arr_temp = array();

	$int_temp = round($filesize/1024,2);
	$arr_temp = explode(".", $int_temp);

	if(count($arr_temp) > 1) {
		if (intval($arr_temp[0]) > 999 || intval($arr_temp[1]) == 0) {
			$rtn = number_format($arr_temp[0]);
		} else {
			$rtn = number_format($arr_temp[0].".".$arr_temp[1],2);
		}
	}

	return $rtn;
}

// byte 단위의 파일크기를 MB로 변환
// 크기가 1GB 이상이 되면 소수점을 생략하고
// 그이하일 경우는 소수점 첫째자리까지 출력한다.
function get_file_size_to_mb($filesize)
{
	$int_temp = "";
	$arr_temp = array();

	$int_temp = round($filesize/1024/1024,2);
	$arr_temp = explode(".", $int_temp);

	if(count($arr_temp) > 1) {
		if (intval($arr_temp[0]) > 999 || intval($arr_temp[1]) == 0) {
			$rtn = number_format($arr_temp[0]);
		} else {
			$rtn = number_format($arr_temp[0].".".$arr_temp[1],1);
		}
	}else{
		$rtn = number_format($int_temp);
	}

	return $rtn;
}

// 이미지 파일 확장자인지 확인
function is_image_file_ext($filename)
{
	$file_ext = get_file_ext($filename);
	return in_array($file_ext, explode('|', 'gif|jpg|jpeg|png'));
}

// 영상 파일 확장자인지 확인
function is_video_file_ext($filename)
{
	$file_ext = get_file_ext($filename);
	return in_array($file_ext, explode('|', 'mp4'));
}

// path 가장 마지막 string return
function get_basename($path)
{
	$path_arr = explode('/', $path);
	return end($path_arr);
}

// link 상 파일 존재하는지 여부 확인하기
function is_url_exists($url)
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	if ($code == 200) {
		$status = true;
	} else {
		$status = false;
	}
	curl_close($ch);
	return $status;
}

function get_file_size($filesize, $add_unit_text = false)
{
	$units = ['g','m','k'];
	$unit = get_file_size_unit($filesize);

	$muliplier = 3 - array_search($unit, $units);
	$divisor = pow(1024, $muliplier);

	$int_temp = round($filesize/$divisor,2);
	$arr_temp = explode(".", $int_temp);

	if(count($arr_temp) > 1) {
		if (intval($arr_temp[0]) > 999 || intval($arr_temp[1]) == 0) {
			$rtn = number_format($arr_temp[0]);
		} else {
			$rtn = number_format($arr_temp[0].".".$arr_temp[1],1);
		}
	}else{
		$rtn = number_format($int_temp);
	}

	if($add_unit_text) $rtn .= strtoupper($unit.'B');

	return $rtn;

}

// 용량을 표시하는 문자열로부터 실제 바이크 크기를 반환
function convert_to_bytes($size)
{
	$size = trim($size);
	$last = strtolower($size[strlen($size)-1]); // 마지막 글자 (단위)
	$size = (int) filter_var($size, FILTER_SANITIZE_NUMBER_INT); // 숫자만 추출

	switch ($last) {
		case 'g':
			$size *= 1024;
		case 'm':
			$size *= 1024;
		case 'k':
			$size *= 1024;
	}

	return $size; // 바이트 단위로 변환
}
