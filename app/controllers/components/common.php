<?php
class CommonComponent extends Object {

	///////////////////////////////////////////////////////////////////
	//監査ログ出力
	///////////////////////////////////////////////////////////////////
	function QueryAuditLogWrite($user_id,$query){
		if ( DIR_AUDIT_LOG == "" ){ return 1; }
		if ( ! is_dir(DIR_AUDIT_LOG) ){ return 1; }

		//監査ログ
		$today = getdate();
		$audit_log_file=sprintf("%s/webhive_query.log",DIR_AUDIT_LOG);
		$ymdhms=sprintf("%04d/%02d/%02d %02d:%02d:%02d",
			$today['year'],$today['mon'],$today['mday'],
			$today['hours'],$today['minutes'],$today['seconds']);

		//改行置換
		$query2=ereg_replace("\t"," ",$query);
		$query2=ereg_replace("\r|\n","%n",$query2);

		//監査ログ出力
		if ( !($fp=fopen($audit_log_file,"a")) ){ return 1; }
		fputs($fp,"$ymdhms\t$user_id\t$query2\n");
		fclose($fp);
		return 0;
	}

	///////////////////////////////////////////////////////////////////
	//ディレクトリ作成
	///////////////////////////////////////////////////////////////////
	function MakeDirectory($u_userid, $u_id=""){
		$d1=DIR_REQUEST."/${u_userid}";
		if ( !is_dir($d1) ){
			if ( !mkdir($d1) ){ return 1; }
		}

		$d2=DIR_RESULT."/${u_userid}";
		if ( !is_dir($d2) ){
			if ( !mkdir($d2) ){ return 1; }
		}

		$d3=DIR_UPLOAD."/${u_userid}";
		if ( !is_dir($d3) ){
			if ( !mkdir($d3) ){ return 1; }
		}
		
		if ( $u_id != "" ){
			$d3=DIR_UPLOAD."/${u_userid}/$u_id";
			if ( !is_dir($d3) ){
				if ( !mkdir($d3) ){ return 1; }
			}
		}
		return 0;
	}

	///////////////////////////////////////////////////////////////////
	//HiveQL結果ファイルの一覧を返す
	///////////////////////////////////////////////////////////////////
	function GetResultFiles($fin_file) {
		$filnms="";

		if ( !file_exists($fin_file) ){ return ""; }
		if ( !($fp=fopen($fin_file,"r")) ){ return ""; }
		while(!feof($fp)){
			$w = fgets($fp,10240);
			$w=str_replace(array("\r\n","\n","\r"), '', $w);
			if ( !eregi("^OUT:",$w) ){ continue; }
			$arr=split('/',$w);
			if ( $filnms != "" ){ $filnms.=","; }
			$filnms.=end($arr);
		}
		fclose($fp);

		return $filnms;
	}

	///////////////////////////////////////////////////////////////////
	//HiveQL結果ファイルの内容を返す
	///////////////////////////////////////////////////////////////////
	function FileRead($csv_file,$dtype) {
		$datas=array();
		$line=0;

		if ( !($fp=fopen($csv_file,"r")) ){
			return $datas;
		}
		while(!feof($fp)){
			$w = fgets($fp, 512);
			if ( $dtype == "csv" ){
				$w=mb_convert_encoding(rtrim($w),"UTF-8","SJIS");
			}else{
				$w=rtrim($w);
			}
			$datas[]=array("data"=>$w);
			$line++;
			if ( $line >= 1000 ){ break; }
		}
		fclose($fp);
		return $datas;
	}

	///////////////////////////////////////////////////////////////////
	//圧縮HiveQL結果ファイルの内容を返す
	///////////////////////////////////////////////////////////////////
	function ZipFileRead($zip_file,$dtype) {
		$datas=array();
		$line=0;

		//ライブラリ
		set_include_path(get_include_path() .PATH_SEPARATOR. DIR_ARCH_LIB);
		require_once "File/Archive.php";

		//圧縮ファイル読み込み
		$arr=array();
		$source = File_Archive::read( "$zip_file/" );
		while( true ){
			$w=CommonComponent::ZipFileReadBuffer($source);
			if ( $w == "" ){ break; }
			if ( $dtype == "csv" ){
				$w=mb_convert_encoding(rtrim($w),"UTF-8","SJIS");
			}else{
				$w=rtrim($w);
			}
			$datas[]=array("data"=>$w);
			$line++;
			if ( $line >= 1000 ){ break; }
		}
		$source->close();

		return $datas;
	}

	function ZipFileReadBuffer($source){
		$retval="";

		while ( true ){
			$ret=$source->getData( 1 );
			if ( $ret == "" ){ return $retval; }
			$retval.=$ret;
			if ( $ret == "\n" ){ return $retval; }
		}
	}

	///////////////////////////////////////////////////////////////////
	//Hive前処理（クエリ実行権限チェック）
	///////////////////////////////////////////////////////////////////
	function HiveBefore($u_userid,$u_query){

		//ユーザ情報検索
		$users=$this->Users->find('all', array('conditions' => "username='$u_userid'"));

		//ユーザ情報設定
		if ( count($users) == 0 ){
			$u_auth=LDAP_AUTH;
			$u_database="";
		}else{
			$u_auth=$users[0]['Users']['authority'];
			$u_database=$users[0]['Users']['hive_database'];
		}

		//権限チェック
		if ( CommonComponent::CheckSQLAuth($u_auth,$u_query) != 0 ){
			return array(1,"");
		}

		//接続先Hive Server設定
		$hive_database="";
		if ( $u_database != "" ){ $hive_database=$u_database; }
		return array(0,$hive_database);
	}

	///////////////////////////////////////////////////////////////////
	//SQLの実行権限チェック
	///////////////////////////////////////////////////////////////////
	function CheckSQLAuth($user_auth, $u_query) {

		//許可
		$ck=Configure::read("USER_AUTH_${user_auth}.query");
		if ( $ck == "" ){ return 1; }
		if ( eregi($ck,"all") ){ return 0; }

		//コメント除外処理
		$arr=preg_split("/[\r\n]/",$u_query);
		$u_query2="";
		for ($i=0; $i<count($arr); $i++){
			if ( eregi('^--',$arr[$i]) ){ continue; }
			$u_query2.="$arr[$i]\n";
		}

		//権限チェック
		$arr=preg_split("/;/",$u_query2);
		for ($i=0; $i<count($arr); $i++){
			$arr[$i]=str_replace(array("\r\n","\n","\r"), ' ', $arr[$i]);
			$arr[$i]=ltrim($arr[$i]);
			if ( $arr[$i] == "" ){ continue; }
			if ( !eregi($ck,$arr[$i]) ){ return 2; }
		}
		return 0;
	}

	///////////////////////////////////////////////////////////////////
	//HiveQLのexplainチェック
	///////////////////////////////////////////////////////////////////
	function CheckSQLexplain($u_userid,$u_id) {
		$stage_cnt=0;
		$mapreduce_cnt=0;
		$line_cnt=0;

		$exp_file=DIR_RESULT."/${u_userid}/${u_id}.exp";

		//予想時間
		if ( !($fp=fopen($exp_file,"r")) ){ return array(-1,-1,-1); }
		while(!feof($fp)){
			$data = fgets($fp, 512);
			$line_cnt++;
			if ( eregi("^Time taken:",$data) ){ $stage_cnt++; }
			if ( eregi("Map Reduce",$data) ){ $mapreduce_cnt++; }
		}
		fclose($fp);

		return array($stage_cnt,$mapreduce_cnt,$line_cnt);
	}


	///////////////////////////////////////////////////////////////////
	// Job実行状況の取得
	///////////////////////////////////////////////////////////////////
	function GetJobInfo($hql_file,$out_file,$fin_file,$pid_file) {
		$err_flg=0;
		$total_p=1;
		$stage_p=0;
		$map_p=0;
		$reduce_p=0;
		$stage_cnt=0;
		$stage_max=0;
		$query_cnt=0;
		$query_max=0;
		$total_cnt=0;
		$total_max=0;
		$file_cnt=0;
		$file_max=0;

		//子プロセス処理中チェック
		if ( file_exists($pid_file) ){
			//子プロセスのPID取得
			$fp=fopen($pid_file,"r");
			$pid = fgets($fp, 1024);
			fclose($fp);
			$pid=str_replace(array("\r\n","\n","\r"), '', $pid);

			//子プロセス異常終了チェック(PIDファイルのpidが存在するかチェック)
			if ( $pid != "start" ){
				if ( !posix_kill($pid,0) ){
					unlink($pid_file);
					return array(100,$total_p,$stage_p,$map_p,$reduce_p);
				}
			}
		}

		//実行数(クエリ数)取得
		$datas="";
		if ( !($fp=fopen($hql_file,"r")) ){
			return array(100,$total_p,$stage_p,$map_p,$reduce_p);
		}
		while(!feof($fp)){
			$data = fgets($fp, 1024);
			$data=rtrim($data);
			$datas = $datas . $data;
		}
		fclose($fp);
		$querys=preg_split("/;/",$datas);
		for ( $i=0; $i<count($querys); $i++){
			if ( $querys[$i] != "" ){ $query_max++; }
		}

		//hiveログより情報取得
		if ( !($fp=fopen($out_file,"r")) ){
			return array(100,$total_p,$stage_p,$map_p,$reduce_p);
		}
		while(!feof($fp)){
			$data = fgets($fp, 1024);
			$data=rtrim($data);
			if ( ereg("^Total MapReduce jobs",$data) ){
				$arr=preg_split("/=/",$data);
				$stage_max=ereg_replace(" ","",$arr[1]);
				$stage_cnt=0;
			}
			if ( ereg("^Ended Job =",$data) ){
				$stage_cnt++;
			}
			if ( ereg("map =",$data) and  eregi("reduce =",$data) ){
				$arr=preg_split("/[ ,%]+/",$data);
				$map_p=$arr[6];
				$reduce_p=$arr[9];
			}
			if ( ereg("^Time taken:",$data) ){
				$query_cnt++;
			}
			if ( ereg("^FAILED:",$data) ){
				$err_flg=100;
			}
		}
		fclose($fp);

		//ログファイルより結果取得
		$file_max=OUTPUT_FILE_LIMIT;
		if ( file_exists($fin_file) ){
		 	if ( !($fp=fopen($fin_file,"r")) ){
				return array(100,$total_p,$stage_p,$map_p,$reduce_p);
			}
			while(!feof($fp)){
				$data = fgets($fp, 1024);
				if ( eregi("^OUT:",$data) ){ $file_cnt++; }
				if ( eregi("^WAR:",$data) ){ $err_flg++; }
				if ( eregi("^ERR:",$data) ){ $err_flg = $err_flg + 100; }
			}
			fclose($fp);
		}

		//合計の進捗率
		if ( $query_max != 0 ){
			$total_p=floor((50 / $query_max ) * $query_cnt );
		}
		if ( $file_max != 0 ){
			$total_p=$total_p + floor( (50 / $file_max ) * $file_cnt );
		}
		if ( $total_p < 0 ){ $total_p=0; }
		if ( $total_p > 100 ){ $total_p=100; }

		//stageの進捗率
		if ( $stage_max != 0 ){
			$stage_p=floor( (100 / $stage_max) * $stage_cnt );
		}
		if ( $stage_p < 0 ){ $stage_p=0; }
		if ( $stage_p > 100 ){ $stage_p=100; }

		//$this->log("query=$query_cnt/$query_max stage=$stage_cnt/$stage_max file=$file_cnt/$file_max ($total_p,$stage_p,$map_p,$reduce_p)",LOG_DEBUG);

		return array($err_flg,$total_p,$stage_p,$map_p,$reduce_p);
	}

	///////////////////////////////////////////////////////////////////
	// JobIDの取得
	///////////////////////////////////////////////////////////////////
	function GetJobId($out_file) {
		$jobid="";

		//ログファイルより情報取得
		if ( !($fp=fopen($out_file,"r")) ){
			return "";
		}
		while(!feof($fp)){
			$data = fgets($fp, 512);
			$data=rtrim($data);
			if ( ereg("^Starting Job = ",$data) ){
				$arr=preg_split("/[ ,]+/",$data);
				$jobid=$arr[3];
			}
		}
		fclose($fp);

		return $jobid;
	}

	///////////////////////////////////////////////////////////////////
	//クエリ実行結果を更新
	///////////////////////////////////////////////////////////////////
	function UpdateRunhistsResult($u_rid,$u_res){

		$this->log("UpdateRunhistsResult($u_rid,$u_res)",LOG_DEBUG);

		//実行履歴検索
		$qwk=$this->Runhists->find('all', array('conditions' => "rid='$u_rid'"));
		if ( count($qwk) == 0 ){
			return 1;
		}

		//処理結果更新
		$runlog['Runhists']['id'] = $qwk[0]['Runhists']['id'];
		$runlog['Runhists']['rsts'] = $u_res;
		$runlog['Runhists']['findate'] = date('Y-m-d H:i:s');
		if ( !($this->Runhists->save($runlog, array('id','rsts','findate') )) ){
			return 2;
		}

		return 0;
	}
}
?>
