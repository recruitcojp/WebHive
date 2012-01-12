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
if ( empty($argv[1]) or empty($argv[2]) ){
	//print "ERR:parameter error\n";
	exit(1);
}
$hive_host=$argv[1];
$hive_port=$argv[2];

//defaultは常に出力
print("default\n");

///////////////////////////////////////////////////////////////////
// Hive接続
///////////////////////////////////////////////////////////////////
$transport = new TSocket($hive_host,$hive_port);
$transport->setSendTimeout(HIVE_SEND_TIMEOUT);
$transport->setRecvTimeout(HIVE_RECV_TIMEOUT);
$protocol = new TBinaryProtocol($transport);
$client = new ThriftHiveClient($protocol);
$transport->open();

//データベース名取得
$shell_ret=hive_exec($client,"show databases");

$transport->close();

exit($shell_ret);


///////////////////////////////////////////////////////////////////
// HiveQL実行
///////////////////////////////////////////////////////////////////
function hive_exec($client,$sql) {

	// HiveQL実行
	try{
		$client->execute("$sql");
	}catch(Exception $e){
		$msg=$e->getMessage();
		//print("ERR:$msg\n");
		return 1;
	}

	// HiveQL結果出力
	while( ($arr=$client->fetchN(10000)) ){
		foreach ($arr as $row){
			if ( $row == "default" ){ continue; }
			print("$row\n");
		}
	}

	return 0;
}

?>
