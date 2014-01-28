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
				$dt=date("Y/m/d H:i",strtotime($querys[$i]['Runhists']['created']));
				$u_rsts=$querys[$i]['Runhists']['rsts'];
				$u_rid=$querys[$i]['Runhists']['rid'];

				$hql_file=DIR_REQUEST."/${u_userid}/${u_rid}.hql";
				$out_file=DIR_RESULT."/${u_userid}/${u_rid}.out";
				$fin_file=DIR_RESULT."/${u_userid}/${u_rid}.fin";
				$pid_file=DIR_RESULT."/${u_userid}/${u_rid}.pid";

				//処理結果ファイル
				$rfil="";
				if ( $u_rsts == 200 ){
					$rfil=CommonComponent::GetResultFiles($fin_file);
				}

				//処理中の場合
				if ( $u_rsts == 0 or $u_rsts == "" ){
					list($err_flg,$total_p,$stage_p,$map_p,$reduce_p)=CommonComponent::GetJobInfo($hql_file,$out_file,$fin_file,$pid_file);
					if ( !file_exists($pid_file) ){
						if ( $err_flg >= 100 ){
							$u_rsts=501;
						}elseif ( $err_flg != 0 ){
							$u_rsts=401;
						}else{
							$u_rsts=200;
						}
						CommonComponent::UpdateRunhistsResult($u_rid,$u_rsts);
					}
				}

				//処理状況
				if ( $u_rsts == 0 or $u_rsts == "" ){
					$u_rsts="処理中";
				}elseif ( $u_rsts == 100 ){
					$u_rsts="キャンセル";
				}elseif ( $u_rsts == 200 ){
					$u_rsts="正常終了";
				}elseif ( $u_rsts <= 500 ){
					$u_rsts="警告あり";
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
					"sql"=>$querys[$i]['Runhists']['query']
					);
			}

		//変更履歴
		}elseif ( $u_out == "mod" ){

			//最新クエリ
			if ( $u_qid != "" ){
				$querys=$this->Hiveqls->findById($u_qid);
				$dt=date("Y/m/d H:i",strtotime($querys['Hiveqls']['created']));
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
				$dt=date("Y/m/d H:i",strtotime($querys[$i]['Queryhists']['created']));
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
				$dt=date("Y/m/d H:i",strtotime($querys[$i]['Hiveqls']['created']));
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
	//ユーザ一覧を返す
	///////////////////////////////////////////////////////////////////
	function users() {

		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//ユーザ一覧
		$u_users=$this->Users->find('all', array() );
		//$this->log($u_users,LOG_DEBUG);

		//結果セット
		$id=0;
		$datas=array();
		$total=count($u_users);
		for($i=0; $i<$total; $i++){
			if ( $u_users[$i]['Users']['authority'] == 1 ){ continue; }

			$auth=$u_users[$i]['Users']['authority'];
			$wk=Configure::read("USER_AUTH_${auth}");
			if ( $wk == "" ){ continue; }

			$datas[]=array(
				"id"=>$id,
				"userid"=>$u_users[$i]['Users']['id'], 
				"username"=>$u_users[$i]['Users']['username'],
				"rolename"=>$wk['rolename'],
				"authority"=>$u_users[$i]['Users']['authority'],
				"hive_database"=>$u_users[$i]['Users']['hive_database']
			);
			$id++;
		}

		$this->set("result" , array("total" => "$total","row" => $datas));
	}

	///////////////////////////////////////////////////////////////////
	//ロール一覧を返す
	///////////////////////////////////////////////////////////////////
	function roles() {

		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//結果セット
		$datas=array();
		$total=0;
		for($i=2; $i<10; $i++){
			$wk=Configure::read("USER_AUTH_${i}");
			//$this->log($wk,LOG_DEBUG);
			if ( $wk == "" ){ continue; }
			$datas[]=array("id"=>$i, "rolename"=>$wk['rolename'], "query"=>$wk['query']);
		}

		$this->set("result" , array("total" => "$total","row" => $datas));
	}

	///////////////////////////////////////////////////////////////////
	//ユーザ権限更新
	///////////////////////////////////////////////////////////////////
	function usermodify() {

		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//パラメータ解析
		//$this->log($this->params,LOG_DEBUG);
		if ( isset( $this->params['form']['userid'] ) ){
			$u_userid=$this->params['form']['userid'];
		}else{
			$u_userid="";
		}
		if ( isset( $this->params['form']['userauth'] ) ){
			$u_userauth=$this->params['form']['userauth'];
		}else{
			$u_userauth="";
		}
		if ( isset( $this->params['form']['hive_database'] ) ){
			$u_hive_database=$this->params['form']['hive_database'];
		}else{
			$u_hive_database="";
		}
		$this->log("usermodify() [$u_userid][$u_userauth][$u_hive_database]",LOG_DEBUG);

		//パラメータチェック
		if ( $u_userid == "" or $u_userauth == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		//DB更新
		$reg=array();
		$reg['Users']['id']=$u_userid;
		$reg['Users']['authority']=$u_userauth;
		$reg['Users']['hive_database']=$u_hive_database;
		$this->Users->create();
		if ( !($this->Users->save($reg, array('id','authority','hive_database'))) ){
			$this->set("result" , array("result" => "db access error"));
			return;
		}

		$this->set("result" , array("result" => "ok"));
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
		if ( isset( $this->params['form']['u'] ) ){
			$u_userid=$this->params['form']['u'];
		}else{
			$u_userid="";
		}
		//$this->log("parameter=[$u_userid]",LOG_DEBUG);
		if( $u_userid == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		//事前処理
		list($res,$hive_database)=CommonComponent::HiveBefore($u_userid,"show database");
		if ( $res != 0 ){
			$this->set("result" , array("result" => "permission error"));
			return;
		}

		//全データベース名取得
		$cmd = CMD_HIVE . " -e 'show databases;' 2>/dev/null";
		$this->log("CMD=$cmd",LOG_DEBUG);
		exec($cmd,$result,$retval);
		$this->log("CMD=$cmd => $retval",LOG_DEBUG);

		#結果セット
		$datas=array();
		$total=count($result);
		for($i=0; $i<$total; $i++){
			if ( CommonComponent::CheckDatabase($hive_database,$result[$i]) != 0 ){ continue; }
			$datas[]=array("id"=>$result[$i], "caption"=>$result[$i]);
		}

		$this->set("result" , array("total" => "$total","row" => $datas));
	}

	///////////////////////////////////////////////////////////////////
	//hiveテーブル名を返す
	///////////////////////////////////////////////////////////////////
	function table() {

		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//パラメータ解析
		if ( isset( $this->params['form']['u'] ) ){
			$u_userid=$this->params['form']['u'];
		}else{
			$u_userid="";
		}
		if ( isset( $this->params['form']['db'] ) ){
			$u_dbname=$this->params['form']['db'];
		}else{
			$u_dbname="";
		}
		//$this->log("parameter=[$u_userid]",LOG_DEBUG);
		if( $u_userid == "" or $u_dbname == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		//事前処理
		list($res,$hive_database)=CommonComponent::HiveBefore($u_userid,"show tables;");
		if ( $res != 0 ){
			$this->set("result" , array("result" => "permission error"));
			return;
		}

		//show tables実行
		$cmd = CMD_HIVE . " -e 'use $u_dbname; show tables;' 2>/dev/null";
		$this->log("CMD=$cmd",LOG_DEBUG);
		exec($cmd,$result,$retval);
		$this->log("CMD=$cmd => $retval",LOG_DEBUG);

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
		$u_query=htmlspecialchars_decode($this->params['form']['q']);
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
	//データベース作成
	///////////////////////////////////////////////////////////////////
	function credb() {
		App::import('Sanitize');
		//ajaxリクエスト以外
		if( !$this->RequestHandler->isAjax() ) {
			$this->set("result" , array("result" => "not ajax"));
			return;
		}

		//パラメータ解析
		$u_userid=$this->params['form']['u'];
		$u_dbname=strip_tags(htmlspecialchars_decode($this->params['form']['name']), ENT_QUOTES);
		if ( $u_userid == "" or $u_dbname == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		//事前処理
		$u_query="create database $u_dbname;";
		list($res,$hive_database)=CommonComponent::HiveBefore($u_userid,$u_query);	
		if ( $res != 0 ){
			$this->set("result" , array("result" => "permission error"));
			return;
		}

		//クエリ監査ログ出力
		CommonComponent::QueryAuditLogWrite($u_userid,$u_query);

		//データベース作成
		$cmd = CMD_HIVE . " -e '" . $u_query . "' 2>/dev/null";
		$this->log("CMD=$cmd",LOG_DEBUG);
		exec("$cmd",$result,$retval);
		$this->log("CMD=$cmd => $retval",LOG_DEBUG);
		if ( $retval == 0 ){
			$this->set("result" , array("result" => "ok"));
			return;
		}
		$this->set("result" , array("result" => "execute error"));
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
		list($res,$hive_database)=CommonComponent::HiveBefore($u_userid,$u_query);	
		if ( $res != 0 ){
			$this->set("result" , array("result" => "許可されていないクエリです"));
			return;
		}

		//リクエストID発行
		if ( CommonComponent::MakeDirectory($u_userid) != 0 ){
			$this->set("result" , array("result" => "create directory error"));
			return;
		}
		$u_id=sprintf("%s_%05d",date("YmdHis"),getmypid());
		$hql_file=DIR_REQUEST."/${u_userid}/${u_id}.hql";
		$chk_file=DIR_REQUEST."/${u_userid}/${u_id}.chk";
		$exp_file=DIR_RESULT."/${u_userid}/${u_id}.exp";

		//コメント行変換処理
		$arr=preg_split("/[\r\n]/",$u_query);
		$u_query2="";
		for ($i=0; $i<count($arr); $i++){
			if ( eregi('^--',$arr[$i]) ){
				$u_query2.="$arr[$i];\n";
			}else{
				$u_query2.="$arr[$i]\n";
			}
		}

		//リクエストファイル作成
		if ( !($fp=fopen($hql_file,"w")) ){
			$this->set("result" , array("result" => "file open error", "id" => "$u_id"));
			return;
		}
		fputs($fp,"use ${u_database};\n");
		$arr=preg_split("/;/",$u_query2);
		for ($i=0; $i<count($arr); $i++){
			$arr[$i]=str_replace(array("\r\n","\n","\r","\t"), ' ', $arr[$i]);
			$arr[$i]=ltrim($arr[$i]);
			if ( $arr[$i] == "" ){ continue; }
			$ret=fputs($fp,"$arr[$i];\n");
		}
		fclose($fp);

		//EXPLAINファイル作成
		if ( !($fp=fopen($chk_file,"w")) ){
			$this->set("result" , array("result" => "file open error", "id" => "$u_id"));
			return;
		}
		fputs($fp,"use ${u_database};\n");
		$arr=preg_split("/;/",$u_query2);
		for ($i=0; $i<count($arr); $i++){
			$arr[$i]=str_replace(array("\r\n","\n","\r","\t"), ' ', $arr[$i]);
			$arr[$i]=ltrim($arr[$i]);
			if ( $arr[$i] == "" ){ continue; }
			if ( eregi('^--',$arr[$i]) ){
				$ret=fputs($fp,"$arr[$i]\n");
				continue;
			}
			if ( eregi(SQL_EXPLAIN_EXCLUDE,$arr[$i]) ){
				$ret=fputs($fp,"$arr[$i];\n");
			}else{
				$ret=fputs($fp,"explain $arr[$i];\n");
			}
		}
		fclose($fp);

		// explainによるHiveQLチェック
		$cmd = CMD_HIVE . " -v -f $chk_file > $exp_file 2>&1";
		$this->log("CMD=$cmd",LOG_DEBUG);
		exec($cmd,$result,$retval);
		$this->log("CMD=$cmd => $retval",LOG_DEBUG);
		if ( !file_exists($exp_file) ){
			$this->set("result" , array("result" => "explain error", "id" => "$u_id"));
			return;
		}
		if ( $retval != 0 ){
			$this->set("result" , array("result" => "query error", "id" => "$u_id"));
			return;
		}

		// explain結果判定
		list($stage_cnt,$mapreduce_cnt,$line_cnt)=CommonComponent::CheckSQLexplain($u_userid,$u_id);
		if ( $stage_cnt < 0 ){
			$this->set("result" , array("result" => "unknown", "id" => "$u_id"));
			return;
		}

		// データベース実行権限チェック
		$u_databases=DATABASE_PERMISSION;
		$u_user=$this->Users->find('all', array( "conditions" => "username='$u_userid'", "limit"=>1));
		if(!empty($u_user[0]['Users']['hive_database'])){
			$u_databases=$u_user[0]['Users']['hive_database'];	
		}
		$chk_result=CommonComponent::CheckExplainDatabase($u_userid,$u_id,$u_databases);
		if ( $chk_result != 0 ){
			$this->set("result" , array("result" => "指定されたデータベースへのアクセスが許可されていません", "id" => "$u_id"));
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
		if ( $u_userid == "" or $u_id == "" ){
			$this->set("result" , array("result" => "parameter error"));
			return;
		}

		//ディレクトリ
		if ( CommonComponent::MakeDirectory($u_userid) != 0 ){
			$this->set("result" , array("result" => "create directory error"));
			return;
		}

		//SQL文取得
		$u_query="";
		$hql_file=DIR_REQUEST."/${u_userid}/${u_id}.hql";
		if ( !($fp=fopen($hql_file,"r")) ){
			$this->set("result" , array("result" => "file open error"));
			return;
		}
		while(!feof($fp)){
			$data = fgets($fp, 512);
			$u_query.=$data;
		}
		fclose($fp);

		//SQLファイルを書き換える(select文の前にselect文のコメントを挿入)
		if ( !($fp=fopen($hql_file,"w")) ){
			$this->set("result" , array("result" => "file open error", "id" => "$u_id"));
			return;
		}
		$arr=preg_split("/;/",$u_query);
		for ($i=0; $i<count($arr); $i++){
			$arr[$i]=str_replace(array("\r\n","\n","\r","\t"), ' ', $arr[$i]);
			$arr[$i]=ltrim($arr[$i]);
			if ( $arr[$i] == "" ){ continue; }
			if ( eregi('^--',$arr[$i]) ){
				$ret=fputs($fp,"$arr[$i]\n");
			}else{
				if ( eregi('^select',$arr[$i]) ){
					$ret=fputs($fp,"--$arr[$i]\n");
				}
				$ret=fputs($fp,"$arr[$i];\n");
			}
		}
		fclose($fp);

		//クエリの実行制限チェック
		list($res,$hive_database)=CommonComponent::HiveBefore($u_userid,$u_query);	
		if ( $res != 0 ){
			$this->set("result" , array("result" => "許可されていないクエリです"));
			return;
		}

		//同時実行数制限
		$run_cnt=CommonComponent::GetQueryExecuteNum();	
		//$this->log("CNT=$run_cnt",LOG_DEBUG);
		if ( $run_cnt >= WEBHIVE_MAX_REQUEST ){
			$this->set("result" , array("result" => "クエリ実行数が制限を超えました。しばらくたってから再実行してください"));
			return;
		}

		//クエリ実行履歴出力
		$runlog['Runhists']['username']=$u_userid;
		$runlog['Runhists']['hive_database']=$hive_database;
		$runlog['Runhists']['query']=$u_query;
		$runlog['Runhists']['rid']=$u_id;
		$runlog['Runhists']['rsts']=0;
		if ( !($this->Runhists->save($runlog, array('username','hive_database','query','rid','rsts') )) ){
			$this->set("result" , array("result" => "db access error"));
			return;
		}
		$runlog['Runhists']['id'] = $this->Runhists->getLastInsertID();

		//クエリ監査ログ出力
		CommonComponent::QueryAuditLogWrite($u_userid,$u_query);

		//HiveQLのバックグラウンド実行
		$cmd=CMD_PHP . " " . CMD_HIVE_SHELL . " $u_userid $u_id"; 
		$this->log("CMD=$cmd",LOG_DEBUG);
		exec("$cmd > /dev/null 2>&1 &",$result,$retval);
		$this->log("CMD=$cmd => $retval",LOG_DEBUG);
		$this->set("result" , array("result" => "ok", "id" => "$u_id"));
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

		//ファイル名
		$hql_file=DIR_REQUEST."/${u_userid}/${u_id}.hql";
		$out_file=DIR_RESULT."/${u_userid}/${u_id}.out";
		$fin_file=DIR_RESULT."/${u_userid}/${u_id}.fin";
		$pid_file=DIR_RESULT."/${u_userid}/${u_id}.pid";

		//hive処理チェック
		if ( !file_exists($hql_file) ){
			$this->set("result" , array("result" => "file open error", "id" => "$u_id"));
			return;
		}
		if ( !file_exists($out_file) ){
			$this->set("result" , array("result" => "progress", "id" => "$u_id"));
			return;
		}
		list($err_flg,$total_p,$stage_p,$map_p,$reduce_p)=CommonComponent::GetJobInfo($hql_file,$out_file,$fin_file,$pid_file);
		if ( $err_flg >= 100 ){
			$this->set("result" , array("result" => "execute error", "id" => "$u_id"));
			CommonComponent::UpdateRunhistsResult($u_id,501);
			return;
		}

		//子プロセス処理中チェック
		if ( file_exists($pid_file) ){
			$this->set("result" , array("result" => "progress", "id" => "$u_id", "total"=>"$total_p", "stage"=>"$stage_p", "map"=>"$map_p", "reduce"=>"$reduce_p"));
			return;
		}

		//結果ファイルの一覧を取得
		$filnms=CommonComponent::GetResultFiles($fin_file);
		if ( $filnms == "" ){
			$this->set("result" , array("result" => "unknown error", "id" => "$u_id"));
			CommonComponent::UpdateRunhistsResult($u_id,503);
			return;
		}

		#サイズ制限あり
		if ( $err_flg != 0 ){
			$this->set("result" , array("result" => "warning", "id" => "$u_id", "filnm" => "$filnms"));
			CommonComponent::UpdateRunhistsResult($u_id,401);
			return;
		}
		
		//処理終了
		$this->set("result" , array("result" => "ok", "id" => "$u_id", "filnm" => "$filnms"));
		CommonComponent::UpdateRunhistsResult($u_id,200);
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
		if ( $u_dtype == "csv" ){

			//処理中チェック
			$pid_file=DIR_RESULT."/${u_userid}/${u_id}.pid";
			if ( file_exists($pid_file) ){
				$this->set("result" , array("result" => "ok", "id" => "$u_id", "datas"=>"", "dtype" => $u_dtype));
				return;
			}

			//zipファイルの場合
			$read_file=DIR_RESULT."/${u_userid}/${u_id}_000.zip";
			if ( file_exists($read_file) ){
				$this->log("$read_file",LOG_DEBUG);
				$datas=CommonComponent::ZipFileRead($read_file,$u_dtype);
				$this->set("result" , array("result" => "ok", "datas" => $datas, "dtype" => $u_dtype));
				return;
			}

			//gzipファイルの場合
			$read_file=DIR_RESULT."/${u_userid}/${u_id}_000.csv.gz";
			if ( file_exists($read_file) ){
				$this->log("$read_file",LOG_DEBUG);
				$datas=CommonComponent::GZipFileRead($read_file,$u_dtype);
				$this->set("result" , array("result" => "ok", "datas" => $datas, "dtype" => $u_dtype));
				return;
			}

		}else{
			$read_file=DIR_RESULT."/${u_userid}/${u_id}.${u_dtype}";
			$this->log("$read_file",LOG_DEBUG);
			if ( file_exists($read_file) ){
				$datas=CommonComponent::FileRead($read_file,$u_dtype);
				$this->set("result" , array("result" => "ok", "datas" => $datas, "dtype" => $u_dtype));
				return;
			}
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

		//ファイル名
		$out_file=DIR_RESULT."/${u_userid}/${u_id}.out";
		$pid_file=DIR_RESULT."/${u_userid}/${u_id}.pid";

		//JOBキャンセルループ
		for ($cancel_loop=0; $cancel_loop < JOBCANCEL_RETRY_MAX; $cancel_loop++){

			$cancel_loop_max=JOBCANCEL_RETRY_MAX;
			$this->log("JOB CANCEL ($cancel_loop/$cancel_loop_max)",LOG_DEBUG);

			//hiveクライアントスクリプトが完了しているか？
			if ( !file_exists($pid_file) ){
				$this->set("result" , array("result" => "ok"));
				return;
			}

			//hadoop JobID取得
			$jobid=CommonComponent::GetJobId($out_file);

			//JOBキャンセル
			if ( $jobid != "" ){
				$cmd=CMD_HADOOP." job \-kill $jobid";
				$this->log("CMD=$cmd",LOG_DEBUG);
				exec("$cmd > /dev/null 2>&1",$result,$retval);
				$this->log("CMD=$cmd => $retval",LOG_DEBUG);
			}

			sleep(JOBCANCEL_RETRY_WAIT);
		}

		$this->set("result" , array("result" => "retry over"));
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
