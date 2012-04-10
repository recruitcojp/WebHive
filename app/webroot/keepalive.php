<?php

define('ROOT', dirname(dirname(__FILE__)));
require_once(ROOT."/config/app.php");

$GLOBALS['THRIFT_ROOT'] = DIR_HIVE_LIB;
require_once $GLOBALS['THRIFT_ROOT'] . '/packages/hive_service/ThriftHive.php';
require_once $GLOBALS['THRIFT_ROOT'] . '/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php';

//hiveチェック
$ret=hive_check(HIVE_HOST, HIVE_PORT, 30000, 30000);
if ( $ret == 0 ){
	echo "OK";
}else{
	echo "NG";
}

exit(0);

function hive_check($hive_host,$hive_port,$hive_send_timeout,$hive_recv_timeout) {
	$shell_ret=0;

	try{
		$transport = new TSocket($hive_host,$hive_port);
		$transport->setSendTimeout($hive_send_timeout);
		$transport->setRecvTimeout($hive_recv_timeout);
		$protocol = new TBinaryProtocol($transport);
		$client = new ThriftHiveClient($protocol);
		$transport->open();
		$client->execute('show databases');
		//var_dump($client->fetchAll());
	}catch(Exception $e){
		$shell_ret=1;
	}

	$transport->close();
	return $shell_ret;
}

?>
