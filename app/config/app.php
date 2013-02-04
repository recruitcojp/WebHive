<?php

//アプリバージョン
define("APP_TITLE", "WebHive Ver.2.1");			

//LANG設定
define("APP_LANG", "ja_JP.utf8");				

//0=未認証状態でユーザ管理画面表示を許可 1=admin権限でのみ表示可能
define("USER_ADMIN", "1");				

//ディレクトリ定義
define("DIR_REQUEST", "/var/www/html/WebHive/request");
define("DIR_RESULT", "/var/www/html/WebHive/result");
define("DIR_ARCH_LIB", "/var/www/html/WebHive/app/libs");
define("DIR_AUDIT_LOG", "/var/www/html/WebHive/audit");
define("DIR_ENTITY", "/var/www/html/WebHive/entity");
define("DIR_UPLOAD", "/var/www/html/WebHive/upload");

//コマンド定義
define("CMD_HADOOP", "/usr/bin/hadoop");
define("CMD_HIVE", "/usr/bin/hive");
define("CMD_HIVE_SHELL", "/var/www/html/WebHive/app/modules/hive_request.php");
define("CMD_PHP", "/usr/bin/php");
define("CMD_ZIP", "/usr/bin/zip");

//hiveのデフォルト接続先データベース
define("HIVE_DATABASE", "default");

//データアップロード先のHIVEデータベース
define("HIVE_DATABASE_UPLOAD", "temp");	

//出力ファイル制限
define("OUTPUT_FILE_MAX", "268435456");		//１ファイルの最大サイズ(byte)
define("OUTPUT_FILE_LIMIT", "20");		//最大ファイル数

//インフォメーションファイル
define("INFORMATION_FILE", "/var/www/html/WebHive/app/config/information.txt");

//タイトルカスタマイズ
define("TIPS_FILE", "/var/www/html/WebHive/app/config/tips.txt");
define("TITLE_URL1", "");
define("TITLE_URL2", "");
define("BANNER_IMG", "");

//権限毎のHiveQL実行制限
Configure::write('USER_AUTH_1', array(
"query"=>"all",
"query_mgr"=>1,
"query_reg"=>1,
"query_del"=>1,
"create_db"=>1,
"data_upload"=>1,
"file_upload"=>0,
));

Configure::write('USER_AUTH_2', array(
"query"=>"^use|^select|^show|^desc|^set|^add|^delete|^create temporary function|^create table|^alter table|^insert|^from",
"query_mgr"=>0,
"query_reg"=>1,
"query_del"=>1,
"create_db"=>0,
"data_upload"=>1,
"file_upload"=>0,
));

Configure::write('USER_AUTH_3', array(
"query"=>"^use|^select|^show|^desc|^set|^add|^delete|^create temporary function",
"query_mgr"=>0,
"query_reg"=>1,
"query_del"=>1,
"create_db"=>0,
"data_upload"=>1,
"file_upload"=>0,
));

Configure::write('USER_AUTH_4', array(
"query"=>"^use|^select|^show|^desc|^set|^add|^delete|^create temporary function",
"query_mgr"=>0,
"query_reg"=>0,
"query_del"=>0,
"create_db"=>0,
"data_upload"=>0,
"file_upload"=>0,
));

//explainチェックの除外コマンド
define("SQL_EXPLAIN_EXCLUDE", "^--|^use|^add|^delete|^set|^create temporary function");

//LDAP設定(LDAPを利用する場合のみ必要)
define("LDAP_HOST", "");
define("LDAP_RDN", "");
define("LDAP_AUTH", "3");

?>
