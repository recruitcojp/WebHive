<?php
class ResultController extends AppController {
	var $name = 'Result';
	var $components = array('Auth','Common');

	function download() { 

		$p_username=$this->params['pass'][0];
		$p_filename=$this->params['pass'][1];
		$p_filename_full=DIR_RESULT . "/$p_username/$p_filename";

		//ファイルアクセスチェック
		$user=$this->Auth->user();
		$username=$user['User']['username'];
		if ( $username == $p_username ){
			$chk_flg=0;
		}else{
			$chk_flg=1;
		}
		if ( !file_exists($p_filename_full) ){
			$chk_flg=2;
		}
		$this->log("File Download USER=[$username] FILE=[$p_filename_full] CHK=[$chk_flg]",LOG_DEBUG);
		if ( $chk_flg != 0 ){
			$this->redirect('/errors');
		}

		//ダウンロード
		//$this->view = 'Media';
		//$parts = pathinfo($p_filename_full);
		//$params = array(
		//	'id' => $parts['basename'],
		//	'name' => $parts['filename'],
		//	'extension' => $parts['extension'],
		//	'download' => true,
		//	'path' => $parts['dirname'].DS
		//);
		//$this->set($params);

		//ダウンロード
		$this->autoRender = false;
		Configure::write('debug', 0);
		$parts = pathinfo($p_filename_full);
		header('Content-Disposition: attachment; filename='.$parts['basename']);
		header('Content-Length: '.filesize($p_filename_full));
		header('Content-Type: application/octet-stream');
		readfile($p_filename_full);
	}

	function beforeRender() {
	}

	function beforeFilter() {
	}
}
?>
