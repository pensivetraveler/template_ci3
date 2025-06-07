<?php

class MktLogger
{
    const PREFIX = "itkey";

    private $table = array();
    private $stats = array();
    private $is_regi = array();
    private $media_list = array();
    private $randing_list = array();
    private $get_field = array();

    function __construct()
    {
        //테이블 정의
        $this->table['connect']		= self::PREFIX . "_connect";
        $this->table['formdata']	= self::PREFIX . "_connect_formdata";

        //진행상황
        $this->stats['noselect']	= "미선택";
        $this->stats['ok']			= "통화완료";
        $this->stats['leave']		= "부재중";
        $this->stats['off']			= "수신거부";
        $this->stats['duplicate']	= "중복";
        $this->stats['no']			= "무효";

        //등록여부
        $this->is_regi['noselect']	= "미선택";
        $this->is_regi['success']	= "success";
        $this->is_regi['drop']		= "drop";

        //매체
        $this->media_list['ad.search.naver.com']	= "네이버 검색광고";
        $this->media_list['cafe.naver.com']			= "네이버 카페";
        $this->media_list['blog.naver.com']			= "네이버 블로그";
        $this->media_list['search.naver.com']		= "네이버 검색";
        $this->media_list['kin.naver.com']			= "네이버 지식인";
        $this->media_list['comic.naver.com']		= "네이버 웹툰";
        $this->media_list['adtg.widerplanet.com']	= "adtg.widerplanet.com";
        $this->media_list['antg.widerplanet.com']	= "antg.widerplanet.com";
        $this->media_list['ad.tpmn.co.kr']			= "ad.tpmn.co.kr";
        $this->media_list['ads.priel.co.kr']		= "ads.priel.co.kr";
        $this->media_list['adsvc2.wisenut.co.kr']	= "adsvc2.wisenut.co.kr";
        $this->media_list['news.huvle.com']			= "허블뉴스";
        $this->media_list['golfinjob.com']			= "golfinjob.com";
        $this->media_list['clien.net']				= "클리앙";
        $this->media_list['ruliweb.com']			= "루리웹";
        $this->media_list['mimint.co.kr']			= "mimint.co.kr";
        $this->media_list['msd.eduplex.net']		= "msd.eduplex.net";
        $this->media_list['eduplex.net']			= "eduplex.net";
        $this->media_list['facebook.com']			= "페이스북";
        $this->media_list['dcinside.com']			= "디씨인사이드";

        $this->get_local[] = "서울";
        $this->get_local[] = "경기";
        $this->get_local[] = "인천";
        $this->get_local[] = "부산";
        $this->get_local[] = "대구";
        $this->get_local[] = "대전";
        $this->get_local[] = "경남";
        $this->get_local[] = "전남";
        $this->get_local[] = "충남";
        $this->get_local[] = "광주";
        $this->get_local[] = "울산";
        $this->get_local[] = "경북";
        $this->get_local[] = "전북";
        $this->get_local[] = "충북";
        $this->get_local[] = "강원";
        $this->get_local[] = "제주";
        $this->get_local[] = "세종";

    }

    public function get_stats($key){
        if($key){
            return $this->stats[$key];
        }
        else{
            return $this->stats;
        }
    }

    public function get_is_regi($key){
        if($key){
            return $this->is_regi[$key];
        }
        else{
            return $this->is_regi;
        }
    }

    public function get_media_list(){
        return $this->media_list;
    }

    public function get_field_list(){
        return $this->get_field;
    }

    public function get_local_list(){
        return $this->get_local;
    }

    public function get_randing_list()
    {
        //변수에서 가져오다가, 실시간 쌓이는 DB에서 가져오는걸로 변경함...
        $sql = "SELECT randing_url FROM ".$this->table['formdata']." GROUP BY randing_url";
        $result = parent::query($sql);
        while($row = parent::result_assoc($result))
        {
            if($row['randing_url']){
                $this->randing_list[$row['randing_url']] = $row['randing_url'];
            }
        }

        return $this->randing_list;
    }

    public function set_connect_in($request_uri, $http_referer)
    {
        //if($request_uri && $http_referer)
        if($request_uri) //2018-08-09 수정
        {
            $sql = "
				SELECT		COUNT(*) AS CNT
				FROM		".$this->table['connect']."
				WHERE		ip			= '".$_SERVER['REMOTE_ADDR']."'	AND
				page_url	= '".$request_uri."'			AND
				DATE_SUB(NOW(), INTERVAL +1 MINUTE) < time_in
				";
            if($_SERVER['REMOTE_ADDR'] == "125.185.248.62")
            {
                //echo $sql;
            }
            $past_connect = parent::query_assoc($sql);
            $past_connect = $past_connect['CNT'];

            if($past_connect > 0)
            {
                //최근 1시간안에 접속이 있으면, 계속 접속하고있는 것으로 간주하겠다.
            }
            else
            {
                //최근 1시간안에 접속이 없으면, 새로 접속한 것으로 간주하겠다.
                $sql = "
					INSERT	".$this->table['connect']."
					SET		ip			= '".$_SERVER['REMOTE_ADDR']."',
					page_url	= '".$request_uri."',
					referer		= '".$http_referer."',
					agent		= '".$_SERVER['HTTP_USER_AGENT']."',
					time_in		= NOW()
					";
                parent::query($sql);
            }
        }
    }

    //사이트를 나갈때
    public function set_connect_out($request_uri)
    {
        $sql = "
			SELECT		idx
			FROM		".$this->table['connect']."
			WHERE		ip			= '".$_SERVER['REMOTE_ADDR']."' AND
			page_url	= '".$request_uri."'
			ORDER BY	idx DESC
			LIMIT		0, 1
			";
        $last_connect = parent::query_assoc($sql);
        $last_idx = $last_connect['idx'];

        $sql = "
			UPDATE		".$this->table['connect']."
			SET			time_out	= NOW()
			WHERE		idx			= ".$last_idx."
			";
        //write_log("logtest.txt", $sql);
        parent::query($sql);
    }

    //카카오 클릭
    public function set_connect_kakao($place)
    {
        //중복체크, 카카오는 입력폼이 아닌 그냥 단순 클릭이기 때문에, 중복클릭으로 인한 form insert를 막자.
        $sql = "
			SELECT		COUNT(*) AS CNT
			FROM		".$this->table['formdata']."	AS FD
			INNER JOIN	".$this->table['connect']."		AS C
			ON			FD.connect_idx = C.idx
			WHERE		C.ip			= '".$_SERVER['REMOTE_ADDR']."' AND
			FD.submit_type	= 'kakao' AND
			FD.place		= '".xss($place)."'
			";
        $last_kakao = parent::query_assoc($sql);
        if($last_kakao['CNT'] > 0){
            return;
        }

        $arg = array(
            "name"			=> "",
            "phone"			=> "",
            "submit_type"	=> "kakao",
            "place"			=> $place
        );
        $this->set_formdata($arg);
    }

    public function start_script()
    {
        $script = "";

        if($_SERVER['REMOTE_ADDR'] == "125.185.248.62")
        {
            //echo $_SERVER['HTTP_REFERER'];
        }

        //if($_SERVER['REQUEST_URI'] && $_SERVER['HTTP_REFERER'])
        if($_SERVER['REQUEST_URI']) //2018-08-09 레퍼러없어도 등록함
        {
            $data = array();
            $data['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
            $data['HTTP_REFERER'] = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
            $data = json_encode($data);
            $data = urlencode($data);

            $script = "
				<script type='text/javascript'>
				window.addEventListener('load', function(e) {(new Image()).src = '/mktlog/_library_connect_log_act.php?type=connect_in&data=".$data."';
				});

				window.addEventListener('beforeunload', function(e) {(new Image()).src = '/mktlog/_library_connect_log_act.php?type=connect_out&data=".$data."';
				});
				</script>
				";
        }

        return $script;
    }
}