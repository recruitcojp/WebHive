<?php
class ApisController extends AppController {
	var $name = 'Apis';
	var $components = array('RequestHandler','Common');
	var $helpers = array('Html', 'Form', 'Javascript');
	var $user;

	///////////////////////////////////////////////////////////////////
	//登録済みのHiveQLを返す
	///////////////////////////////////////////////////////////////////
	function select() {

		$querys=$this->Hiveqls->find('all', array('order' => 'created','limit'=>1000));
		$total=count($querys);

		$datas=array();
		for($i=0; $i<$total; $i++){
			$datas[]=array("id"=>$querys[$i]['Hiveqls']['id'], 
				"title"=>$querys[$i]['Hiveqls']['title'], 
				"sql"=>$querys[$i]['Hiveqls']['query']);
		}

		$this->set("result" , array("total" => "$total","row" => $datas));

	}

	///////////////////////////////////////////////////////////////////
	//HiveQL登録処理
	///////////////////////////////////////////////////////////////////
	function register() {
		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//パラメータ解析
		$u_userid=$this->params['form']['u'];
		$u_title=$this->params['form']['t'];
		$u_query=$this->params['form']['q'];
		if ( $u_userid == "" or $u_query == "" or $u_title == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		//DBへの登録
		$reg=array();
		$reg['Hiveqls']['title']=$u_title;
		$reg['Hiveqls']['query']=$u_query;
		$this->Hiveqls->create();
		if ( !($this->Hiveqls->save($reg,array('title','query'))) ){
			$this->set("result" , array("result" => "db access error"));
			return;
		}

		$this->set("result" , array("result" => "ok"));
	}

	///////////////////////////////////////////////////////////////////
	//HiveQL登録削除
	///////////////////////////////////////////////////////////////////
	function delete() {
		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//パラメータ解析
		$u_userid=$this->params['form']['u'];
		$u_id=$this->params['form']['id'];
		if ( $u_userid == "" or $u_id == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		//HiveQL削除
		if ( !($this->Hiveqls->delete($u_id)) ){
			$this->set("result" , array("result" => "db access error"));
			return;
		}

		$this->set("result" , array("result" => "ok"));
	}

	///////////////////////////////////////////////////////////////////
	//実行チェック
	///////////////////////////////////////////////////////////////////
	function explain() {
		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//パラメータ解析
		$u_userid=$this->params['form']['u'];
		$u_query=$this->params['form']['q'];
		if ( $u_userid == "" or $u_query == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		//権限チェック
		$users=$this->Users->find('all', array('conditions' => "username='$u_userid'"));
		if ( count($users) == 0 ){
			$this->set("result" , array("result" => "user no data"));
			return;
		}
		if ( CommonComponent::CheckSQLAuth($users[0]['Users']['authority'],$u_query) != 0 ){
			$this->set("result" , array("result" => "permission error"));
			return;
		}

		//接続先Hive Server設定
		$hive_host=HIVE_HOST;
		$hive_port=HIVE_PORT;
		if ( $users[0]['Users']['hive_host'] != "" ){
			 $hive_host=$users[0]['Users']['hive_host'];
		}
		if ( $users[0]['Users']['hive_port'] != "" ){
			 $hive_port=$users[0]['Users']['hive_port'];
		}

		//リクエストID発行
		$u_id=sprintf("%s_%05d",date("YmdHis"),getmypid());
		$req_file=DIR_REQUEST."/${u_id}.hql";
		$exp_file=DIR_RESULT."/${u_id}.exp";

		//リクエストファイル作成
		if ( !($fp=fopen($req_file,"w")) ){
			$this->set("result" , array("result" => "file open error", "id" => "$u_id"));
			return;
		}
		fputs($fp,"$u_query");
		fclose($fp);

		// explainによるHiveQLチェック
		$cmd=CMD_EXPLAIN_SHELL . " $u_id $hive_host $hive_port";
		exec("/usr/bin/php $cmd",$result,$retval);
		$this->log("CMD=$cmd => $retval",LOG_DEBUG);
		if ( !file_exists($exp_file) ){
			$this->set("result" , array("result" => "explain error", "id" => "$u_id"));
			return;
		}

		// explain結果判定
		list($stage_cnt,$mapreduce_cnt,$line_cnt)=CommonComponent::CheckSQLexplain($u_id);
		if ( $stage_cnt < 0 ){
			$this->set("result" , array("result" => "unknown", "id" => "$u_id"));
			return;
		}

		//時間がかかりそうな場合は結果確認画面を出す
		if ( $stage_cnt > 2 or $mapreduce_cnt > 0 or $line_cnt > 30 ){
			$msg="処理数=$stage_cnt MapReduce数=$mapreduce_cnt コスト=$line_cnt";
			$this->set("result" , array("result" => "check", "id" => "$u_id", "msg"=>"$msg"));
		}else{
			$this->set("result" , array("result" => "ok", "id" => "$u_id"));
		}
	}


	///////////////////////////////////////////////////////////////////
	//HiveQLリクエスト
	///////////////////////////////////////////////////////////////////
	function request() {
		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//パラメータ解析
		$u_userid=$this->params['form']['u'];
		$u_id=$this->params['form']['id'];
		if ( $u_userid == "" or $u_id == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		$req_file=DIR_REQUEST."/${u_id}.hql";
		$out_file=DIR_RESULT."/${u_id}.out";
		$csv_file=DIR_RESULT."/${u_id}.csv";

		//SQL文取得
		$u_query="";
		if ( !($fp=fopen($req_file,"r")) ){
			$this->set("result" , array("result" => "file open error"));
			return;
		}
		while(!feof($fp)){
			$data = fgets($fp, 512);
			$u_query .= $data;
		}
		fclose($fp);

		//接続先Hive Server設定
		$users=$this->Users->find('all', array('conditions' => "username='$u_userid'"));
		if ( count($users) == 0 ){
			$this->set("result" , array("result" => "user no data"));
			return;
		}
		$hive_host=HIVE_HOST;
		$hive_port=HIVE_PORT;
		if ( $users[0]['Users']['hive_host'] != "" ){ 
			 $hive_host=$users[0]['Users']['hive_host'];
		}
		if ( $users[0]['Users']['hive_port'] != "" ){ 
			 $hive_port=$users[0]['Users']['hive_port'];
		}

		//HiveQLをバックグラウンド実行するか判定
		$bg_flg=0;
		$arr=preg_split("/;/",$u_query);
		for ($i=0; $i<count($arr); $i++){
			$arr[$i]=str_replace(array("\r\n","\n","\r"), ' ', $arr[$i]);
			$arr[$i]=ltrim($arr[$i]);
			if ( $arr[$i] == "" ){ continue; }
			if ( eregi("^ls|^show|^desc",$arr[$i]) ){ continue; }
			$bg_flg=1;
		}

		//バックグラウンド実行
		if ( $bg_flg == 1 ){
			$cmd=CMD_HIVE_SHELL . " $u_id $hive_host $hive_port";
			exec("/usr/bin/php $cmd >> $out_file 2>&1 &",$result,$retval);
			$this->log("CMD=$cmd => $retval",LOG_DEBUG);
			sleep(1);
			$this->set("result" , array("result" => "ok", "id" => "$u_id"));
			return;
		}

		//フォアグラウンド実行
		$cmd=CMD_HIVE_SHELL . " $u_id $hive_host $hive_port";
		exec("/usr/bin/php $cmd >> $out_file 2>&1",$result,$retval);
		$this->log("CMD=$cmd => $retval",LOG_DEBUG);
		if ( $retval == 0 ){
			if ( file_exists($csv_file) ){
				$this->set("result" , array("result" => "fin", "id" => "$u_id"));
				return;
			}
		}
		$this->set("result" , array("result" => "execute error", "id" => "$u_id"));
	}

	///////////////////////////////////////////////////////////////////
	//HiveQL処理完了チェック
	///////////////////////////////////////////////////////////////////
	function check() {
		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//パラメータ解析
		$u_userid=$this->params['form']['u'];
		$u_id=$this->params['form']['id'];
		if ( $u_userid == "" or $u_id == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		$csv_file=DIR_RESULT."/${u_id}.csv";
		$out_file=DIR_RESULT."/${u_id}.out";
		$exp_file=DIR_RESULT."/${u_id}.exp";
		$pid_file=DIR_RESULT."/${u_id}.pid";

		//処理中
		if ( !file_exists($out_file) ){
			$this->set("result" , array("result" => "progress", "id" => "$u_id"));
			return;
		}

		//hive処理異常チェック
		list($err_flg,$stage_p,$map_p,$reduce_p)=CommonComponent::GetJobInfo($exp_file,$out_file);
		if ( $err_flg != 0 ){
			$this->set("result" , array("result" => "execute error", "id" => "$u_id"));
			return;
		}

		//pidファイルチェック
		if ( file_exists($pid_file) ){
			$fp=fopen($pid_file,"r");
			$pid = fgets($fp, 1024);
			fclose($fp);
			$pid=str_replace(array("\r\n","\n","\r"), '', $pid);
			if ( !posix_kill($pid,0) ){
				$this->set("result" , array("result" => "process error", "id" => "$u_id"));
				unlink($pid_file);
				return;
			}
		}

		//結果ファイルチェック
		if ( file_exists($csv_file) ){
			$this->set("result" , array("result" => "ok", "id" => "$u_id"));
			return;
		}

		//処理中
		$this->set("result" , array("result" => "progress", "id" => "$u_id", "stage"=>"$stage_p", "map"=>"$map_p", "reduce"=>"$reduce_p"));
	}

	///////////////////////////////////////////////////////////////////
	//HiveQL実行結果のダウンロード
	///////////////////////////////////////////////////////////////////
	function download() {
		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//パラメータ解析
		$u_userid=$this->params['form']['u'];
		$u_id=$this->params['form']['id'];
		$u_dtype=$this->params['form']['d'];
		if ( $u_userid == "" or $u_id == "" or $u_dtype == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		$read_file=DIR_RESULT."/${u_id}.${u_dtype}";

		//ファイルの中身を返す
		if ( file_exists($read_file) ){
			$datas=CommonComponent::FileRead($read_file);
			$this->set("result" , array("result" => "ok", "datas" => $datas, "dtype" => $u_dtype));
			return;
		}

		$this->set("result" , array("result" => "no file", "id" => "$u_id", "datas"=>"", "dtype" => $u_dtype));
	}

	///////////////////////////////////////////////////////////////////
	//jobの中断処理
	///////////////////////////////////////////////////////////////////
	function jobcancel() {
		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//パラメータ解析
		$u_userid=$this->params['form']['u'];
		$u_id=$this->params['form']['id'];
		$this->log("RequestID=$u_id",LOG_DEBUG);
		if ( $u_userid == "" or $u_id == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		$out_file=DIR_RESULT."/${u_id}.out";
		$pid_file=DIR_RESULT."/${u_id}.pid";

		//hadoop JobID取得
		$jobid=CommonComponent::GetJobId($out_file);
		if ( $jobid != "" ){
			$cmd=CMD_HADOOP." job \-kill $jobid";
			exec("$cmd > /dev/null 2>&1",$result,$retval);
			$this->log("CMD=$cmd => $retval",LOG_DEBUG);
		}

		//hiveクライアントプロセスが完了しているか？
		if ( !file_exists($pid_file) ){
			$this->set("result" , array("result" => "ok"));
			return;
		}

		//hiveクライアント処理プロセスのPID
		$pid="";
		if ( !($fp=fopen($pid_file,"r")) ){
			$this->set("result" , array("result" => "no process"));
			return;
		}
		$pid=fgets($fp, 512);
		$pid=rtrim($pid);
		fclose($fp);
		if ( $pid == "" ){
			$this->set("result" , array("result" => "no process"));
			return;
		}

		//hiveクライアントプロセスをkill
		$this->log("kill -15 $pid",LOG_DEBUG);
		if ( !posix_kill($pid,15) ){
			$this->log("kill -15 $pid => error",LOG_DEBUG);
		}

		$this->set("result" , array("result" => "ok"));
	}


	///////////////////////////////////////////////////////////////////
	//クラス処理前に呼ばれる関数
	///////////////////////////////////////////////////////////////////
	function beforeFilter() {
		Configure::write('debug', 0);
		$this->RequestHandler->setContent('json');
		$this->RequestHandler->respondAs('application/json; charset=UTF-8');
		$this->layout = "ajax";

		$this->loadModel('Users');
		$this->loadModel('Hiveqls');
	}

	///////////////////////////////////////////////////////////////////
	//クラス処理完了後に呼ばれる関数
	///////////////////////////////////////////////////////////////////
	function beforeRender() {
		#$this->user=$this->Auth->user();
	}
}
?>
