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

		//初期化
		if ( isset( $this->params['form']['q'] ) ){
			$u_out=$this->params['form']['q'];
		}else{
			$u_out="";
		}
		if ( isset( $this->params['form']['u'] ) ){
			$u_userid=$this->params['form']['u'];
		}else{
			$u_userid="";
		}
		if ( isset( $this->params['form']['id'] ) ){
			$u_qid=$this->params['form']['id'];
		}else{
			$u_qid="";
		}
		$total=0;
		$datas=array();

		//実行履歴
		if ( $u_out == "history" ){
			$conditions=array();
			if ( $u_userid != "" ){
				$conditions=array('username' => $u_userid);
			}
			$querys=$this->Runhists->find('all', array( 'conditions' => $conditions, 'order' => 'created desc','limit'=>100));
			$total=count($querys);
			for($i=0; $i<$total; $i++){
				$dt=date("Y/m/d h:i",strtotime($querys[$i]['Runhists']['created']));
				$u_rsts=$querys[$i]['Runhists']['rsts'];
				$u_rid=$querys[$i]['Runhists']['rid'];

				//処理結果ファイル
				$rfil="";
				if ( $u_rsts == 200 ){
					$csv_file=DIR_RESULT."/${u_userid}/${u_rid}.csv";
					$zip_file=DIR_RESULT."/${u_userid}/${u_rid}.csv.zip";
					if ( file_exists($csv_file) ){ $rfil="${u_rid}.csv"; }
					if ( file_exists($zip_file) ){ $rfil="${u_rid}.csv.zip"; }
				}

				//処理状況
				if ( $u_rsts == 0 or $u_rsts == "" ){
					$u_rsts="処理中";
				}elseif ( $u_rsts <= 200 ){
					$u_rsts="正常終了";
				}elseif ( $u_rsts <= 600 ){
					$u_rsts="異常終了";
				}else{
					$u_rsts="";
				}

				$datas[]=array(
					"id"=>"",
					"username"=>$querys[$i]['Runhists']['username'], 
					"title"=>"",
					"created"=>$dt,
					"rid"=>$u_rid,
					"rfil"=>$rfil,
					"rsts"=>$u_rsts,
					"sql"=>str_replace(";",";\n",$querys[$i]['Runhists']['query'])
					);
			}

		//変更履歴
		}elseif ( $u_out == "mod" ){

			//最新クエリ
			if ( $u_qid != "" ){
				$querys=$this->Hiveqls->findById($u_qid);
				$dt=date("Y/m/d h:i",strtotime($querys['Hiveqls']['created']));
				$datas[]=array(
					"id"=>$querys['Hiveqls']['id'], 
					"username"=>$querys['Hiveqls']['username'], 
					"title"=>$querys['Hiveqls']['title'],
					"created"=>$dt,
					"rid"=>"",
					"rfil"=>"",
					"rsts"=>"",
					"sql"=>$querys['Hiveqls']['query']);
			}

			//過去変更クエリ
			$conditions=array('username'=>$u_userid);
			if ( $u_qid != "" ){ $conditions=array('userid'=>$u_userid, 'hiveqls_id'=>$u_qid); }

			$querys=$this->Queryhists->find('all', array( 'conditions' => $conditions, 'order' => 'created desc','limit'=>100));
			$total=count($querys);
			for($i=0; $i<$total; $i++){
				if (  $querys[$i]['Queryhists']['created'] == "" ){ continue; }
				$dt=date("Y/m/d h:i",strtotime($querys[$i]['Queryhists']['created']));
				$datas[]=array(
					"id"=>"",
					"username"=>$querys[$i]['Queryhists']['username'], 
					"title"=>$querys[$i]['Queryhists']['title'],
					"created"=>$dt,
					"rid"=>"",
					"rfil"=>"",
					"rsts"=>"",
					"sql"=>$querys[$i]['Queryhists']['query']);
			}

		//登録クエりを返す
		}else{
			$conditions=array();
			if ( $u_out != "all" and $u_userid != "" ){
				$conditions=array('username' => $u_userid);
			}
			$querys=$this->Hiveqls->find('all', array( 'conditions' => $conditions, 'order' => 'created desc','limit'=>100));
			$total=count($querys);
			for($i=0; $i<$total; $i++){
				$dt=date("Y/m/d h:i",strtotime($querys[$i]['Hiveqls']['created']));
				$datas[]=array(
					"id"=>$querys[$i]['Hiveqls']['id'], 
					"username"=>$querys[$i]['Hiveqls']['username'], 
					"title"=>$querys[$i]['Hiveqls']['title'], 
					"created"=>$dt,
					"rid"=>"",
					"rfil"=>"",
					"rsts"=>"",
					"sql"=>$querys[$i]['Hiveqls']['query']);
			}
		}

		//結果
		$this->set("result" , array("total" => "$total","row" => $datas));

	}

	///////////////////////////////////////////////////////////////////
	//hiveデータベース名を返す
	///////////////////////////////////////////////////////////////////
	function database() {
		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//パラメータ解析
		$u_userid=$this->params['form']['u'];
		if( $u_userid == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		//事前処理
		list($res,$hive_host,$hive_port,$hive_database)=CommonComponent::HiveBefore($u_userid,"show database");
		if ( $res != 0 ){
			$this->set("result" , array("result" => "permission error"));
			return;
		}

		if ( $hive_database == "" ){
			#コマンド実行
			$cmd=CMD_PHP . " " . CMD_HIVE_DATABASE . " " . $hive_host . " " . $hive_port;
			exec($cmd,$result,$retval);
			$this->log("CMD=$cmd => $retval",LOG_DEBUG);
		}else{
			$result=split(",",$hive_database);
		}

		#結果セット
		$datas=array();
		$total=count($result);
		for($i=0; $i<$total; $i++){
			$datas[]=array("id"=>$result[$i], "caption"=>$result[$i]);
		}

		$this->set("result" , array("total" => "$total","row" => $datas));

	}


	///////////////////////////////////////////////////////////////////
	//HiveQL登録処理
	///////////////////////////////////////////////////////////////////
	function register() {
		App::import('Sanitize');
		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//パラメータ解析
		$u_id=$this->params['form']['i'];
		$u_userid=$this->params['form']['u'];
		$u_title=strip_tags(htmlspecialchars_decode($this->params['form']['t']), ENT_QUOTES);
		$u_query=strip_tags(htmlspecialchars_decode($this->params['form']['q']), ENT_QUOTES);
		//$u_title=Sanitize::clean( $u_title );
		//$u_query=Sanitize::clean( $u_query );
		if ( $u_userid == "" or $u_query == "" or $u_title == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		//DB登録値の設定
		$reg=array();
		$reg['Hiveqls']['id']=$u_id;
		$reg['Hiveqls']['username']=$u_userid;
		$reg['Hiveqls']['title']=$u_title;
		$reg['Hiveqls']['query']=$u_query;
		$this->Hiveqls->create();

		//新規登録
		if ( $u_id == "" ){
			if ( !($this->Hiveqls->save($reg, array('username','title','query'))) ){
				$this->set("result" , array("result" => "db access error"));
				return;
			}
			$u_id = $this->Hiveqls->getLastInsertID();
			$this->set("result" , array("result"=>"ok", "qid"=>$u_id));
			return;
		}

		//クエリ更新のアクセスチェック
		if ( !($org_query=$this->Hiveqls->findById($u_id)) ){
			$this->set("result" , array("result"=>"db access error"));
			return;
		}
		if ( trim($org_query['Hiveqls']['username']) != $u_userid ){
			$this->set("result" , array("result" => "permission error"));
			return;
		}
		if ( $org_query['Hiveqls']['title'] == $u_title and $org_query['Hiveqls']['query'] == $u_query ){
			$this->set("result" , array("result" => "no change"));
			return;
		}

		//登録クエリの更新
		if ( !($this->Hiveqls->save($reg, array('id','username','title','query') )) ){
			$this->set("result" , array("result" => "db access error"));
			return;
		}

		//変更前登録クエリの保存
		$hist['Queryhists']['hiveqls_id']=$org_query['Hiveqls']['id'];
		$hist['Queryhists']['username']=$org_query['Hiveqls']['username'];
		$hist['Queryhists']['title']=$org_query['Hiveqls']['title'];
		$hist['Queryhists']['query']=$org_query['Hiveqls']['query'];
		if ( !($this->Queryhists->save($hist, array('hiveqls_id','username','title','query') )) ){
			$this->set("result" , array("result" => "db access error"));
			return;
		}

		$this->set("result" , array("result"=>"ok", "qid"=>$u_id));
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

		//クエリ所有者チェック
		if ( !($org_query=$this->Hiveqls->findById($u_id)) ){
			$this->set("result" , array("result" => "db access error"));
			return;
		}
		if ( trim($org_query['Hiveqls']['username']) != $u_userid ){
			$this->set("result" , array("result" => "permission error"));
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
		$u_database=$this->params['form']['d'];
		$u_query=htmlspecialchars_decode($this->params['form']['q'], ENT_QUOTES);
		if ( $u_database == "" or $u_userid == "" or $u_query == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		//事前処理
		list($res,$hive_host,$hive_port,$hive_database)=CommonComponent::HiveBefore($u_userid,$u_query);	
		if ( $res != 0 ){
			$this->set("result" , array("result" => "permission error"));
			return;
		}

		//リクエストID発行
		if ( CommonComponent::MakeDirectory($u_userid) != 0 ){
			$this->set("result" , array("result" => "create directory error"));
			return;
		}
		$u_id=sprintf("%s_%05d",date("YmdHis"),getmypid());
		$hql_file=DIR_REQUEST."/${u_userid}/${u_id}.hql";
		$exp_file=DIR_RESULT."/${u_userid}/${u_id}.exp";

		//リクエストファイル作成
		if ( !($fp=fopen($hql_file,"w")) ){
			$this->set("result" , array("result" => "file open error", "id" => "$u_id"));
			return;
		}
		fputs($fp,"use ${u_database};");
		fputs($fp,"$u_query");
		fclose($fp);

		// explainによるHiveQLチェック
		$cmd=CMD_PHP . " " . CMD_EXPLAIN_SHELL . " $u_id $hive_host $hive_port $u_userid";
		exec($cmd,$result,$retval);
		$this->log("CMD=$cmd => $retval",LOG_DEBUG);
		if ( !file_exists($exp_file) ){
			$this->set("result" , array("result" => "explain error", "id" => "$u_id"));
			return;
		}

		// explain結果判定
		list($stage_cnt,$mapreduce_cnt,$line_cnt)=CommonComponent::CheckSQLexplain($u_userid,$u_id);
		if ( $stage_cnt < 0 ){
			$this->set("result" , array("result" => "unknown", "id" => "$u_id"));
			return;
		}

		//時間がかかりそうな場合は結果確認画面を出す
		if ( $stage_cnt > 3 or $mapreduce_cnt > 0 or $line_cnt > 35 ){
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
		$u_compress=$this->params['form']['z'];
		$u_column=$this->params['form']['c'];
		if ( $u_userid == "" or $u_id == "" or $u_compress == "" or $u_column == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		//ディレクトリ
		if ( CommonComponent::MakeDirectory($u_userid) != 0 ){
			$this->set("result" , array("result" => "create directory error"));
			return;
		}
		$hql_file=DIR_REQUEST."/${u_userid}/${u_id}.hql";
		$out_file=DIR_RESULT."/${u_userid}/${u_id}.out";
		$csv_file=DIR_RESULT."/${u_userid}/${u_id}.csv";
		$zip_file=DIR_RESULT."/${u_userid}/${u_id}.csv.zip";

		//SQL文取得
		$u_query="";
		if ( !($fp=fopen($hql_file,"r")) ){
			$this->set("result" , array("result" => "file open error"));
			return;
		}
		while(!feof($fp)){
			$data = fgets($fp, 512);
			$u_query.=$data;
		}
		fclose($fp);

		//事前処理
		list($res,$hive_host,$hive_port,$hive_database)=CommonComponent::HiveBefore($u_userid,$u_query);	
		if ( $res != 0 ){
			$this->set("result" , array("result" => "permission error"));
			return;
		}

		//クエリ実行履歴出力
		$runlog['Runhists']['username']=$u_userid;
		$runlog['Runhists']['hive_host']=$hive_host;
		$runlog['Runhists']['hive_port']=$hive_port;
		$runlog['Runhists']['hive_database']=$hive_database;
		$runlog['Runhists']['query']=$u_query;
		$runlog['Runhists']['rid']=$u_id;
		$runlog['Runhists']['rsts']=0;
		if ( !($this->Runhists->save($runlog, array('username','hive_host','hive_port','hive_database','query','rid','rsts') )) ){
			$this->set("result" , array("result" => "db access error"));
			return;
		}
		$runlog['Runhists']['id'] = $this->Runhists->getLastInsertID();

		//HiveQLをバックグラウンド実行するか判定
		$bg_flg=0;
		$arr=preg_split("/;/",$u_query);
		for ($i=0; $i<count($arr); $i++){
			$arr[$i]=str_replace(array("\r\n","\n","\r"), ' ', $arr[$i]);
			$arr[$i]=ltrim($arr[$i]);
			if ( $arr[$i] == "" ){ continue; }
			if ( eregi("^ls|^show|^desc|^use",$arr[$i]) ){ continue; }
			$bg_flg=1;
		}

		//バックグラウンド実行
		$cmd=CMD_PHP . " " . CMD_HIVE_SHELL . " $u_id $hive_host $hive_port $u_userid $u_compress $u_column";
		if ( $bg_flg == 1 ){
			exec("$cmd >> $out_file 2>&1 &",$result,$retval);
			$this->log("CMD=$cmd => $retval",LOG_DEBUG);
			sleep(1);
			$this->set("result" , array("result" => "ok", "id" => "$u_id"));
			return;
		}

		//フォアグラウンド実行
		exec("$cmd >> $out_file 2>&1",$result,$retval);
		$this->log("CMD=$cmd => $retval",LOG_DEBUG);
		if ( $retval == 0 ){

			//結果更新
			if ( CommonComponent::UpdateRunhistsResult($u_id,200) != 0 ){
				$this->set("result" , array("result" => "db access error"));
				return;
			}

			//結果リターン
			if ( file_exists($csv_file) ){
				$this->set("result" , array("result" => "fin", "id" => "$u_id", "filnm" => "${u_id}.csv"));
			}
			if ( file_exists($zip_file) ){
				$this->set("result" , array("result" => "fin", "id" => "$u_id", "filnm" => "${u_id}.csv.zip"));
			}
			return;
		}

		//結果更新
		if ( CommonComponent::UpdateRunhistsResult($u_id,400) != 0 ){
			$this->set("result" , array("result" => "db access error"));
			return;
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

		$csv_file=DIR_RESULT."/${u_userid}/${u_id}.csv";
		$zip_file=DIR_RESULT."/${u_userid}/${u_id}.csv.zip";
		$out_file=DIR_RESULT."/${u_userid}/${u_id}.out";
		$exp_file=DIR_RESULT."/${u_userid}/${u_id}.exp";
		$pid_file=DIR_RESULT."/${u_userid}/${u_id}.pid";

		//処理中
		if ( !file_exists($out_file) ){
			$this->set("result" , array("result" => "progress", "id" => "$u_id"));
			return;
		}

		//hive処理異常チェック
		list($err_flg,$stage_p,$map_p,$reduce_p)=CommonComponent::GetJobInfo($exp_file,$out_file);
		if ( $err_flg != 0 ){
			$this->set("result" , array("result" => "execute error", "id" => "$u_id"));
			CommonComponent::UpdateRunhistsResult($u_id,501);
			return;
		}

		//子プロセス異常終了チェック
		if ( file_exists($pid_file) ){
			$fp=fopen($pid_file,"r");
			$pid = fgets($fp, 1024);
			fclose($fp);
			$pid=str_replace(array("\r\n","\n","\r"), '', $pid);
			if ( !posix_kill($pid,0) ){
				$this->set("result" , array("result" => "process error", "id" => "$u_id"));
				unlink($pid_file);
				CommonComponent::UpdateRunhistsResult($u_id,502);
				return;
			}
		}

		//結果ファイルチェック
		if ( file_exists($zip_file) ){
			$this->set("result" , array("result" => "ok", "id" => "$u_id", "filnm" => "${u_id}.csv.zip"));
			CommonComponent::UpdateRunhistsResult($u_id,200);
			return;
		}
		if ( file_exists($csv_file) ){
			$this->set("result" , array("result" => "ok", "id" => "$u_id", "filnm" => "${u_id}.csv"));
			CommonComponent::UpdateRunhistsResult($u_id,200);
			return;
		}

		//処理が完了したけど、なんらかの原因で結果ファイルが作成されなかった
		if ( !file_exists($pid_file) ){
			$this->set("result" , array("result" => "process error", "id" => "$u_id"));
			CommonComponent::UpdateRunhistsResult($u_id,503);
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

		//ファイルの中身を返す
		$read_file=DIR_RESULT."/${u_userid}/${u_id}.${u_dtype}";
		if ( file_exists($read_file) ){
			$datas=CommonComponent::FileRead($read_file,$u_dtype);
			$this->set("result" , array("result" => "ok", "datas" => $datas, "dtype" => $u_dtype));
			return;
		}

		//zipファイルがある場合
		$read_file=DIR_RESULT."/${u_userid}/${u_id}.${u_dtype}.zip";
		if ( file_exists($read_file) ){
			$datas=CommonComponent::ZipFileRead($read_file,$u_dtype);
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
		CommonComponent::UpdateRunhistsResult($u_id,100);

		$out_file=DIR_RESULT."/${u_userid}/${u_id}.out";
		$pid_file=DIR_RESULT."/${u_userid}/${u_id}.pid";

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
		$this->loadModel('Queryhists');
		$this->loadModel('Runhists');
	}

	///////////////////////////////////////////////////////////////////
	//クラス処理完了後に呼ばれる関数
	///////////////////////////////////////////////////////////////////
	function beforeRender() {
		#$this->user=$this->Auth->user();
	}
}
?>
