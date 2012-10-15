<?php

//アプリバージョン
define("APP_TITLE", "WebHive Ver.2.0");			

//0=未認証状態でユーザ管理画面表示を許可 1=admin権限でのみ表示可能
define("USER_ADMIN", "0");				

//ディレクトリ定義
define("DIR_REQUEST", "/var/www/html/WebHive/request");
define("DIR_RESULT", "/var/www/html/WebHive/result");
define("DIR_ARCH_LIB", "/var/www/html/WebHive/app/libs");
define("DIR_AUDIT_LOG", "/var/www/html/WebHive/audit");
define("DIR_ENTITY", "/var/www/html/WebHive/entity");

//コマンド定義
define("CMD_HADOOP", "/usr/bin/hadoop");
define("CMD_HIVE", "/usr/bin/hive");
define("CMD_HIVE_SHELL", "/var/www/html/WebHive/app/modules/hive_request.php");
define("CMD_PHP", "/usr/bin/php");
define("CMD_ZIP", "/usr/bin/zip");

//hiveのデフォルト接続先データベース
define("HIVE_DATABASE", "default");

//出力ファイル制限
define("OUTPUT_FILE_MAX", "104857600");		//１ファイルの最大サイズ(byte)
define("OUTPUT_FILE_LIMIT", "20");		//最大ファイル数

//インフォメーションファイル
define("INFORMATION_FILE", "/var/www/html/WebHive/app/config/information.txt");

//Tipsファイル
define("TIPS_FILE", "/var/www/html/WebHive/app/config/tips.txt");
define("TIPS_URL", "");

//権限毎のHiveQL実行制限(SQL_AUTH_ADMIN(1) / SQL_AUTH_GUEST(2) / SQL_AUTH_SELECT(3))
define("SQL_AUTH_ADMIN", "");
define("SQL_AUTH_GUEST", "^use|^select|^show|^desc|^set|^ls|^add|^delete|^create temporary function|^create table|^alter table|^insert|^from");
define("SQL_AUTH_SELECT", "^use|^select|^show|^desc|^set|^ls|^add|^delete|^create temporary function");

//explainチェックの除外コマンド
define("SQL_EXPLAIN_EXCLUDE", "^ls|^add|^delete|^set");

//ファイルアップロード許可(1=OK 0=NG)
define("FILE_UPLOAD_FLG", "0");

//LDAP設定(LDAPを利用する場合のみ必要)
define("LDAP_HOST", "");
define("LDAP_RDN", "");
define("LDAP_AUTH", "2");

?>
