<?php
class HelpController extends AppController {
	var $name = 'Help';
	var $components = array('Common');

	function index() {
		//インフォメーションファイル
		$info="";
		if ( ($fp=fopen(INFORMATION_FILE,"r")) ){
			while(!feof($fp)){
				$data = fgets($fp, 512);
				$info .= $data;
			}
			fclose($fp);
		}

		$this->set('app_title_msg', CommonComponent::GetSubTitle());
		$this->set("info" , array("info" => "$info"));
	}
}
?>
