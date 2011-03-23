<?php
class HelpController extends AppController {
	var $name = 'Help';

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

		$this->set("info" , array("info" => "$info"));
	}
}
?>
