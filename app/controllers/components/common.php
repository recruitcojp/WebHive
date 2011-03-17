<?php
class CommonComponent extends Object {

	///////////////////////////////////////////////////////////////////
	//HiveQL結果ファイルの内容を返す
	///////////////////////////////////////////////////////////////////
	function FileRead($csv_file) {
		$datas=array();
		$line=0;

		if ( !($fp=fopen($csv_file,"r")) ){
			return $datas;
		}
		while(!feof($fp)){
			$w = fgets($fp, 512);
			$w=mb_convert_encoding(rtrim($w),"UTF-8","SJIS");
			$datas[]=array("data"=>$w);
			$line++;
			if ( $line >= 1000 ){ break; }
		}
		fclose($fp);
		return $datas;
	}

	///////////////////////////////////////////////////////////////////
	//SQLの実行権限チェック
	///////////////////////////////////////////////////////////////////
	function CheckSQLAuth($user_auth, $u_query) {

		//許可
		$ck="";
		if ( $user_auth == 1 ){
			$ck=SQL_AUTH_ADMIN;
		}elseif ( $user_auth == 2 ){
			$ck=SQL_AUTH_SELECT;
		}elseif ( $user_auth == 3 ){
			$ck=SQL_AUTH_GUEST;
		}else{
			return 1;
		}
		if ( $ck == "" ){ return 0; }

		//権限チェック
		$arr=preg_split("/;/",$u_query);
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
	function CheckSQLexplain($u_id) {
		$stage_cnt=0;
		$mapreduce_cnt=0;
		$line_cnt=0;

		$exp_file=DIR_RESULT."/${u_id}.exp";

		//予想時間
		if ( !($fp=fopen($exp_file,"r")) ){ return array(-1,-1,-1); }
		while(!feof($fp)){
			$data = fgets($fp, 512);
			$line_cnt++;
			if ( eregi("^COMMAND:",$data) ){ $stage_cnt++; }
			if ( eregi("Map Reduce",$data) ){ $mapreduce_cnt++; }
		}
		fclose($fp);

		return array($stage_cnt,$mapreduce_cnt,$line_cnt);
	}



	///////////////////////////////////////////////////////////////////
	// Job実行状況の取得
	///////////////////////////////////////////////////////////////////
	function GetJobInfo($exp_file,$out_file) {
		$err_flg=0;
		$map_p=0;
		$reduce_p=0;
		$stage_p=1;
		$queryid="";
		$proc_cnt=0;
		$total_cnt=0;

		//合計stage数
		if ( !($fp=fopen($exp_file,"r")) ){
			return array(1,$stage_p,$map_p,$reduce_p);
		}
		while(!feof($fp)){
			$data = fgets($fp, 512);
			$data=rtrim($data);
			if ( eregi("^COMMAND:",$data) ){ $total_cnt++; }
			if ( eregi("Map Reduce",$data) ){ $total_cnt++; }
		}
		fclose($fp);

		//APPログファイルより情報取得
		if ( !($fp=fopen($out_file,"r")) ){
			return array(1,$stage_p,$map_p,$reduce_p);
		}
		while(!feof($fp)){
			$data = fgets($fp, 512);
			$data=rtrim($data);
			if ( ereg("^ERR:",$data) ){ $err_flg=1; }
			if ( ereg("^PHP Fatal error",$data) ){ $err_flg=1; }
			if ( ereg("^INF:QUERYID",$data) ){
				list($null,$queryid)=split("=",$data,2);
			}
			if ( ereg("^INF:QEND",$data) ){ $proc_cnt++; }
		}
		fclose($fp);
		if ( $err_flg != 0 ){ 
			return array(1,$stage_p,$map_p,$reduce_p);
		}

		//JOB進捗状況
		list($jobid,$stage_cnt,$map_p,$reduce_p)=CommonComponent::SearchJobInfoWrapper($queryid);

		//stageの進捗率
		if ( $total_cnt != 0 ){
			$stage_p=sprintf("%.0f",(($proc_cnt+$stage_cnt)/$total_cnt*100));
		}
		if ( $stage_p < 1 ){ $stage_p=1; }
		if ( $stage_p > 100 ){ $stage_p=100; }

		return array($err_flg,$stage_p,$map_p,$reduce_p);
	}

	///////////////////////////////////////////////////////////////////
	// JobIDの取得
	///////////////////////////////////////////////////////////////////
	function GetJobId($out_file) {
		$jobid="";
		for ( $i=0; $i<10; $i++){
			//queryid検索
			$queryid=CommonComponent::SearchQueryid($out_file);
			if ( $queryid == "" ){ continue; }

			//jobid検索
			list($jobid,$stage_cnt,$map_p,$reduce_p)=CommonComponent::SearchJobInfoWrapper($queryid);
			if ( $jobid != "" ){ break; }

			sleep(3);
		}
		return $jobid;
	}

	///////////////////////////////////////////////////////////////////
	// APPログファイルよりQueryIDを取得する
	///////////////////////////////////////////////////////////////////
	function SearchQueryid($out_file) {
		$queryid="";

		//結果ファイルよりqueryIDを得る
		if ( !($fp=fopen($out_file,"r")) ){ return ""; }
		while(!feof($fp)){
			$data = fgets($fp, 512);
			$data=rtrim($data);
			if ( ereg("^INF:QUERYID",$data) ){
				list($null,$queryid)=split("=",$data,2);
			}
		}
		fclose($fp);

		return $queryid;
	}

	///////////////////////////////////////////////////////////////////
	//hiveログよりJobIDと進捗状況を取得する(上位)
	///////////////////////////////////////////////////////////////////
	function SearchJobInfoWrapper($queryid) {
		$jobid="";
		$stage_cnt=0;
		$map_p=0;
		$reduce_p=0;

		//ファイル検索
		$ckbase="hive_job_log_".substr($queryid,0,19);
		if ( !($dh = opendir(DIR_HADOOP_TMP)) ){ return array("",0,0,0); }
		while (($hive_file = readdir($dh)) !== false) {
			if ( !ereg("^$ckbase",$hive_file) ){ continue; }
			$hive_file=DIR_HADOOP_TMP."/$hive_file";
			list($jobid,$stage_cnt,$map_p,$reduce_p)=CommonComponent::SearchJobInfo($hive_file,$queryid);
			if ( $jobid != "" ){ break; }
		}
		closedir($dh);

		return array($jobid,$stage_cnt,$map_p,$reduce_p);
	}

	///////////////////////////////////////////////////////////////////
	//hiveログよりJobIDと進捗状況を取得する
	///////////////////////////////////////////////////////////////////
	function SearchJobInfo($hive_file,$queryid) {

		$ckflg=0;
		$jobid="";
		$stage_cnt=0;
		$map_p=0;
		$reduce_p=0;
		$dflg="";

		if ( !($fp=fopen($hive_file,"r")) ){ return array("",0,0,0); }
		while(!feof($fp)){
			$data = fgets($fp, 512);
			$data=rtrim($data);
			if ( ereg("$queryid",$data) ){ $ckflg=1; }
			if ( ereg("^TaskProgress|^TaskEnd",$data) ){
				$arr=preg_split("/[,\" =]+/",$data);
				for ($i=0; $i<count($arr); $i++){
					if ( $arr[$i] == "TASK_HADOOP_ID" ){ $jobid=$arr[$i+1]; }
					if ( $arr[$i] == "map" ){ $dflg="map"; continue; }
					if ( $arr[$i] == "reduce" ){ $dflg="reduce"; continue; }
					if ( $dflg == "" ){ continue; }
					if ( ereg("%",$arr[$i]) ){
						$arr[$i]=str_replace("%", '', $arr[$i]);
						if ( $dflg == "map" ){ $map_p=$arr[$i]; }
						if ( $dflg == "reduce" ){ $reduce_p=$arr[$i]; }
					}
					$dflg="";
				}
			}
			if ( ereg("^TaskEnd",$data) ){
				$stage_cnt++;
			}
		}
		fclose($fp);
		if ( $ckflg == 0 ){ return array("",0,0,0); }

		return array($jobid,$stage_cnt,$map_p,$reduce_p);
	}

}
?>
