<?php

//全体定義
define("APP_TITLE", "WebHive Ver.1.00");		//アプリバージョン
define("USER_ADMIN", "0");				//0=未認証状態でユーザ管理画面表示を許可 1=admin権限でのみ表示可能

//ディレクトリ定義
define("DIR_REQUEST", "/var/www/html/WebHive/request");
define("DIR_RESULT", "/var/www/html/WebHive/result");
define("DIR_HADOOP_TMP", "/tmp/hadoop");
define("DIR_HIVE_LIB", "/var/www/html/WebHive/app/libs/php");

//コマンド定義
define("CMD_HADOOP", "/usr/local/hadoop/bin/hadoop");
define("CMD_HIVE_SHELL", "/var/www/html/WebHive/app/modules/hive_request.php");
define("CMD_EXPLAIN_SHELL", "/var/www/html/WebHive/app/modules/hive_explain.php");

//Hive Server関連
define("HIVE_HOST", "localhost"); 		//hive接続サーバのデフォルト値
define("HIVE_PORT", "10000");			//hive接続ポートのデフォルト値
define("HIVE_SEND_TIMEOUT", "86400000");	//hiveQL送信タイムアウト値
define("HIVE_RECV_TIMEOUT", "86400000");	//hiveQL処理タイムアウト値

//インフォメーションファイル
define("INFORMATION_FILE", "/var/www/html/WebHive/app/config/information.txt");

//ユーザ毎のHiveQL実行制限
define("SQL_AUTH_ADMIN", "");
define("SQL_AUTH_SELECT", "^select|^show|^desc|^set|^ls|^add|^delete|create temporary function");
define("SQL_AUTH_GUEST", "");

//explainチェックでの除外コマンド
define("SQL_EXPLAIN_EXCLUDE", "^ls|^add|^delete|^set");

?>
