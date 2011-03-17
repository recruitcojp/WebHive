<?php

///////////////////////////////////////////////////////////////////
// 共通ライブラリ
///////////////////////////////////////////////////////////////////
define('ROOT', dirname(dirname(__FILE__)));
require_once(ROOT."/config/app.php");

$GLOBALS['THRIFT_ROOT'] = DIR_HIVE_LIB;
require_once $GLOBALS['THRIFT_ROOT'] . '/packages/hive_service/ThriftHive.php';
require_once $GLOBALS['THRIFT_ROOT'] . '/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php';

///////////////////////////////////////////////////////////////////
// 初期化処理
///////////////////////////////////////////////////////////////////
// 引数チェック
if ( empty($argv[1]) or empty($argv[2]) or empty($argv[3]) ){
	print "ERR:parameter error\n";
	exit(1);
}
$hive_id=$argv[1];
$hive_host=$argv[2];
$hive_port=$argv[3];

//ファイル名
$hql_file = DIR_REQUEST."/${hive_id}.hql";
$out_file = DIR_RESULT."/${hive_id}.out";
$csv_file = DIR_RESULT."/${hive_id}.csv";
$exp_file = DIR_RESULT."/${hive_id}.exp";
$tmp_file = DIR_RESULT."/${hive_id}.tmp";
$pid_file = DIR_RESULT."/${hive_id}.pid";

///////////////////////////////////////////////////////////////////
//pidファイル作成
///////////////////////////////////////////////////////////////////
$mypid=getmypid();
if ( !($fp=fopen($pid_file,"w")) ){
	print "ERR:file open error($pid_file)\n";
	exit(1);
}
$ret=fputs($fp,"$mypid\n");
fclose($fp);

///////////////////////////////////////////////////////////////////
// SQL文読み込み
///////////////////////////////////////////////////////////////////
$u_query="";
if ( !($fp=fopen($hql_file,"r")) ){
	print "ERR:file open error($hql_file)\n";
	exit(1);
}
while(!feof($fp)){
	$data = fgets($fp, 1024);
	$u_query.=$data;
}
fclose($fp);
//print "INF:$u_query\n";

///////////////////////////////////////////////////////////////////
// Hive接続
///////////////////////////////////////////////////////////////////
$transport = new TSocket($hive_host,$hive_port);
$transport->setSendTimeout(HIVE_SEND_TIMEOUT);
$transport->setRecvTimeout(HIVE_RECV_TIMEOUT);
$protocol = new TBinaryProtocol($transport);
$client = new ThriftHiveClient($protocol);
$transport->open();
print "INF:hive server($hive_host:$hive_port) connect ok\n";

///////////////////////////////////////////////////////////////////
// 簡単なSQL文を発行(ログを認識させる為)
///////////////////////////////////////////////////////////////////
try{
	$client->execute("show tables");
}catch(Exception $e){
	$msg=$e->getMessage();
	print "ERR:$msg\n";
	exit(1);
}
$res=$client->getQueryPlan();
$queryid=$res->queries[0]->queryId;
print "INF:QUERYID=$queryid\n";

///////////////////////////////////////////////////////////////////
// メイン処理
///////////////////////////////////////////////////////////////////
$shell_ret=0;
if ( !($fp=fopen($tmp_file,"w")) ){
	print "ERR:file open error($tmp_file)\n";
	exit(1);
}

//hiveの実行
$arr=preg_split("/;/",$u_query);
for ($i=0; $i<count($arr); $i++){
	$arr[$i]=str_replace(array("\r\n","\n","\r"), ' ', $arr[$i]);
	$arr[$i]=ltrim($arr[$i]);
	if ( $arr[$i] == "" ){ continue; }
	print "INF:$arr[$i]\n";
	if ( eregi("^ls",$arr[$i]) ){
		if ( hadoop_exec($fp,$arr[$i]) != 0 ){ $shell_ret=1; break; }
	}else{
		if ( hive_exec($fp,$client,$arr[$i]) != 0 ){ $shell_ret=1; break; }
	}
	print "INF:QEND\n";
}

$transport->close();
fclose($fp);

//rename
rename($tmp_file,$csv_file);

//pidファイル削除
unlink($pid_file);

if ( $shell_ret == 0 ){
	print "INF:normal end\n";
	exit(0);
}else{
	print "ERR:abnormal end\n";
	exit(1);
}


///////////////////////////////////////////////////////////////////
// HiveQL実行
///////////////////////////////////////////////////////////////////
function hive_exec($fp,$client,$sql) {

	// HiveQL実行
	try{
		$client->execute($sql);
	}catch(Exception $e){
		$msg=$e->getMessage();
		print "ERR:$msg\n";
		return 1;
	}
	$res=$client->getQueryPlan();
	$queryid=$res->queries[0]->queryId;
	print "INF:queryid=$queryid\n";

	//FETCHループ
	$line=0;
	while( ($arr=$client->fetchN(10000)) ){

		//情報表示
		$line+=count($arr);
		print "INF:ROWS=${line}\n";

		//結果ファイル出力
		foreach ($arr as $row){
			$row.="\n";
			$row=mb_convert_encoding($row,"SJIS","UTF-8");
			$ret=fputs($fp,$row);
			if ( $ret <= 0 ){
				print "ERR:file out error\n";
				return 1;
			}
		}
	}
	return 0;
}

///////////////////////////////////////////////////////////////////
// hadoopコマンド実行
///////////////////////////////////////////////////////////////////
function hadoop_exec($fp,$cmd) {

	//コマンド実行
	$hadoop_cmd=CMD_HADOOP." fs \-$cmd";
	print "INF:$hadoop_cmd\n";
	exec("$hadoop_cmd",$result,$retval);
	print "INF:$hadoop_cmd => $retval\n";
	
	//結果出力
	for ($i=0; $i<count($result); $i++){
		$ret=fputs($fp,"${result[$i]}\n");
		if ( $ret <= 0 ){
			print "ERR:file out error\n";
			return 1;
		}
	}

	return 0;
}

?>
