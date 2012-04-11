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
//データベース名取得
///////////////////////////////////////////////////////////////////
$shell_ret=0;
try{
	$transport = new TSocket($hive_host,$hive_port);
	$transport->setSendTimeout(HIVE_SEND_TIMEOUT);
	$transport->setRecvTimeout(HIVE_RECV_TIMEOUT);
	$protocol = new TBinaryProtocol($transport);
	$client = new ThriftHiveClient($protocol);
	$transport->open();

	$client->execute("show databases");

	// HiveQL結果出力
	while( ($arr=$client->fetchN(10000)) ){
		foreach ($arr as $row){
			if ( $row == "default" ){ continue; }
			print("$row\n");
		}
	}

	$transport->close();

}catch(Exception $e){
	$msg=$e->getMessage();
	$shell_ret=1;
}

exit($shell_ret);

?>
