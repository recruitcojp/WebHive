<?php

///////////////////////////////////////////////////////////////////
// hiveの共通ライブラリ
///////////////////////////////////////////////////////////////////
define('ROOT', dirname(dirname(__FILE__)));
require_once(ROOT."/config/app.php");

//hiveアクセス用のPHPライブラリ
$GLOBALS['THRIFT_ROOT'] = DIR_HIVE_LIB;
require_once $GLOBALS['THRIFT_ROOT'] . '/packages/hive_service/ThriftHive.php';
require_once $GLOBALS['THRIFT_ROOT'] . '/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php';


///////////////////////////////////////////////////////////////////
//初期処理
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
$exp_file = DIR_RESULT."/${hive_id}.exp";

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

///////////////////////////////////////////////////////////////////
// Hive接続
///////////////////////////////////////////////////////////////////
$transport = new TSocket($hive_host,$hive_port);
$transport->setSendTimeout(HIVE_SEND_TIMEOUT);
$transport->setRecvTimeout(HIVE_RECV_TIMEOUT);
$protocol = new TBinaryProtocol($transport);
$client = new ThriftHiveClient($protocol);
$transport->open();

///////////////////////////////////////////////////////////////////
// メイン処理
///////////////////////////////////////////////////////////////////
$shell_ret=0;
if ( !($fp=fopen($exp_file,"w")) ){
	print "ERR:file open error($exp_file)\n";
	exit(1);
}

// HiveQL数分繰り返す。
$arr=preg_split("/;/",$u_query);
for ($i=0; $i<count($arr); $i++){
	$arr[$i]=str_replace(array("\r\n","\n","\r"), ' ', $arr[$i]);
	$arr[$i]=ltrim($arr[$i]);
	if ( $arr[$i] == "" ){ continue; }

	$ret=fputs($fp,"COMMAND:$arr[$i]\n\n");
	if ( eregi(SQL_EXPLAIN_EXCLUDE,$arr[$i]) ){ continue; }
	if ( hive_exec($fp,$client,$arr[$i]) != 0 ){ $shell_ret=1; break; }
}

$transport->close();
fclose($fp);

exit($shell_ret);


///////////////////////////////////////////////////////////////////
// HiveQL実行
///////////////////////////////////////////////////////////////////
function hive_exec($fp,$client,$sql) {
	print "INF:$sql\n";

	// HiveQL実行
	try{
		$client->execute("explain $sql");
	}catch(Exception $e){
		$msg=$e->getMessage();
		$ret=fputs($fp,"ERR:$msg\n");
		return 1;
	}

	// HiveQL結果出力
	while( ($arr=$client->fetchN(10000)) ){
		foreach ($arr as $row){
			$row.="\n";
			$ret=fputs($fp,$row);
			if ( $ret <= 0 ){
				$ret=fputs($fp,"ERR:file out error\n");
				return 1;
			}
		}
	}

	return 0;
}

?>
