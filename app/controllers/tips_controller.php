<?php
class TipsController extends AppController {
	var $name = 'Tips';

	function index() {
		$tips="";
		if ( ($fp=fopen(TIPS_FILE,"r")) ){
			while(!feof($fp)){
				$data = fgets($fp, 512);
				$tips .= $data;
			}
			fclose($fp);
		}

		$this->set("tips" , array("tips" => "$tips"));
	}
}
?>
