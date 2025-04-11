<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Loader extends CI_Loader
{
	protected array $loaded_views = [];
	protected array $unique_views = ['head', 'header', 'footer', 'tail'];

	public function view($view, $vars = array(), $return = FALSE)
	{
		$CI =& get_instance();

		$valid = !empty($view);
		$array = explode('/', $view);
		$filename = end($array);

		if(in_array($filename, $this->unique_views)) {
			if(!in_array($view, config_item('loaded_views'))) {
				// 로드된 뷰를 기록
				$this->loaded_views[] = $view;
				// 로드된 뷰를 config에 기록
				$list = [];
				foreach ($this->loaded_views as $item) {
					$array = explode('/', $item);
					$list[] = end($array);
				}
				$CI->config->set_item('loaded_views', $list);
			}else{
				// 이미 로드된 view 라면 unique view가 아닌 경우에만 return
				$valid = false;
			}
		}

		if($valid && !file_exists(VIEWPATH.$view.'.php')) {
			$valid = false;
			trigger_error("viewApp : View file '$view' does not exist.", E_USER_ERROR);
		}

		// 부모 클래스의 view() 메소드를 호출하여 실제 뷰를 로드
		// 결과를 반환 (view를 return하는 경우에 대비)
		if($valid) return parent::view($view, $vars, $return);
	}

	// 로드된 뷰 목록을 반환하는 메소드
	public function get_loaded_views(): array
	{
		return $this->loaded_views;
	}
}
