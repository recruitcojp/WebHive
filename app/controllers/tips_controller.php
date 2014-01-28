<?php
class TipsController extends AppController {
	var $name = 'Tips';
	var $components = array('Common');

	function index() {
		$tips="";
		if ( ($fp=fopen(TIPS_FILE,"r")) ){
			while(!feof($fp)){
				$data = fgets($fp, 512);
				$tips .= $data;
			}
			fclose($fp);
		}

		$this->set('app_title_msg', CommonComponent::GetSubTitle());
		$this->set("tips" , array("tips" => "$tips"));
	}
}
?>
