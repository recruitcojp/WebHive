#!/bin/sh

BASE_DIR="/var/www/html/WebHive/entity/data"

#データベース情報
LST_DATABASE="$BASE_DIR/database.lst"
echo "[$LST_DATABASE]"
#if [ ! -f $LST_DATABASE ];then
	hive -e "show databases;" > $LST_DATABASE
#fi

while read dbname
do
	#echo "[$dbname]"
	#ディレクトリ作成
	if [ ! -d "$BASE_DIR/$dbname" ];then
		mkdir $BASE_DIR/$dbname
	fi
	if [ ! -d "$BASE_DIR/$dbname/table" ];then
		mkdir $BASE_DIR/$dbname/table
	fi
	if [ ! -d "$BASE_DIR/$dbname/partition" ];then
		mkdir $BASE_DIR/$dbname/partition
	fi

	#テーブル一覧
	LST_TABLE="$BASE_DIR/$dbname/table.lst"
	echo "[$LST_TABLE]"
	#if [ ! -f $LST_TABLE ];then
		hive -e "use $dbname;show tables;" > $LST_TABLE
	#fi

	#テーブル情報
	while read tblname
	do
		LST_DESC="$BASE_DIR/$dbname/table/${tblname}.dat"
		echo "[$LST_DESC]"
		#if [ ! -f $LST_DESC ];then
			hive -e "use $dbname;desc formatted ${tblname};" > $LST_DESC
		#fi
	done < $LST_TABLE

	#パーティション情報
	while read tblname
	do
		LST_PART="$BASE_DIR/$dbname/partition/${tblname}.dat"
		echo "[$LST_PART]"
		#if [ ! -f $LST_PART ];then
			hive -e "use $dbname;show partitions ${tblname};" > $LST_PART
		#fi
	done < $LST_TABLE
	
done < $LST_DATABASE

exit 0
