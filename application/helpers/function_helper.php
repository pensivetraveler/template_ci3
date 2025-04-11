<?php
function get_message_time($datetime) {
	if(strtotime($datetime) + 60 > time()) {
		// 1분 내
		return lang('just now');
	}else {
		$div = strtotime($datetime) - time();
		if(strtotime($datetime) + 60*60 > time()) {
			// 1시간 내
			$div = floor(abs($div)/60);
			return $div.lang('m ago');
		}else if(strtotime($datetime) + 60*60*24 > time()) {
			// 1일 내
			$div = floor(abs($div) / (60 * 60));
			return $div . lang('h ago');
		}else{
			// 수일
			$div = floor(abs($div)/(60*60*24));
			if($div > 1) {
				return $div.lang('days ago');
			}else{
				return $div.lang('day ago');
			}
		}
	}
}

function getStarredId($id) {
	$len = strlen($id);
	$res = substr($id, 0, 2);
	$res .= substr($id, 2, min($len-2,3));
	if(strlen($id) > 5) {
		$res .= substr($id, 5);
	}
	return $res;
}

function getStarredPassword($password) {
	$len = strlen($password);
	$res = substr($password, 0, 2);
	$res .= substr($password, 2, min($len-2,5));
	if(strlen($password) > 7) {
		$res .= substr($password, 7);
	}
	return $res;
}
