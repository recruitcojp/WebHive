<?php
class UploadsController extends AppController {
	var $name = 'Uploads';
	var $components = array('Auth','Common');
	var $helpers = array('Html', 'Form', 'Javascript');
	var $user;
	
	function index() {
		$this->layout = "fileupload";
	}

	///////////////////////////////////////////////////////////////////
	//ファイルアップロード
	///////////////////////////////////////////////////////////////////
	function fileupload() {

		$this->layout = "ajax";

		//ファイルアップロード機能の許可チェック
		$this->user=$this->Auth->user();
		$u_auth=$this->user['User']['authority'];
		$auth_flg=Configure::read("USER_AUTH_${u_auth}");
		if ( $auth_flg['file_upload'] != 1 ){
			$this->set("result" , array("success" => false,'msg'=>'ファイルアップロードが許可されていません。'));
			return;
		}

		//異常チェック
		if ( empty($this->params['form']['outdir']) or empty($this->params['form']['filenm']['name']) ){
			$this->set("result" , array("success" => false,'msg'=>'未入力の項目があります。'));
			return;
		}
		if ( $this->params['form']['outdir']=="" or $this->params['form']['filenm']['name']=="" ){
			$this->set("result" , array("success" => false,'msg'=>'未入力の項目があります。'));
			return;
		}
		if ( $this->params['form']['filenm']['tmp_name'] == "" or $this->params['form']['filenm']['error'] != 0 or $this->params['form']['filenm']['size'] <= 0){
			$this->set("result" , array("success" => false,'msg'=>'ファイルのアップロードでエラーが発生しました。'));
			return;
		}

		$u_filenm=$this->params['form']['filenm']['name'];
		$u_outdir=$this->params['form']['outdir'];
		$u_tmpname=$this->params['form']['filenm']['tmp_name'];

		//HDFS上のディレクトリチェック
		$cmd=CMD_HADOOP." fs \-ls $u_outdir";
		exec("$cmd > /dev/null 2>&1",$result,$retval);
		$this->log("CMD=$cmd => $retval",LOG_DEBUG);
		if ( $retval != 0 ){
			$this->set("result" , array("success" => false,'msg'=>"${u_outdir}へのアクセスでエラーが発生しました。"));
			return;
		}

		//HDFS上にPUTする
		$u_out_file="$u_outdir/$u_filenm";
		$cmd=CMD_HADOOP." fs \-put $u_tmpname $u_out_file";
		exec("$cmd > /dev/null 2>&1",$result,$retval);
		$this->log("$u_tmpname",LOG_DEBUG);
		$this->log("CMD=$cmd => $retval",LOG_DEBUG);
		if ( $retval != 0 ){
			$this->set("result" , array("success" => false,'msg'=>"HDFSへのputが失敗しました。"));
			return;
		}

		$this->set("result" , array("success" => true));
	}

	function beforeFilter() {
	}

	function beforeRender() {
	}
}
?>
