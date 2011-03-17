<?php

define('ROOT', dirname(dirname(__FILE__)));
require_once(ROOT."/config/app.php");

$GLOBALS['THRIFT_ROOT'] = DIR_HIVE_LIB;
require_once $GLOBALS['THRIFT_ROOT'] . '/packages/hive_service/ThriftHive.php';
require_once $GLOBALS['THRIFT_ROOT'] . '/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php';

$transport = new TSocket(HIVE_HOST, HIVE_PORT);
$transport->setSendTimeout(HIVE_SEND_TIMEOUT); 
$transport->setRecvTimeout(HIVE_RECV_TIMEOUT);
$protocol = new TBinaryProtocol($transport);
$client = new ThriftHiveClient($protocol);
$transport->open();

$client->execute('show tables');
var_dump($client->fetchAll());

$transport->close();

?>
