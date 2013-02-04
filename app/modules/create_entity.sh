#!/bin/sh

. /opt/mapr/mapr_bashrc

BASE_DIR="/var/www/html/WebHive/entity/data"

#データベース一覧情報
LST_DATABASE="$BASE_DIR/database.lst"
echo "[$LST_DATABASE]"
hive -S -e "show databases;" > $LST_DATABASE

while read dbname
do
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
	hive -S -e "use $dbname;show tables;" > $LST_TABLE

	#テーブル情報取得
	TBL_HQL_FILE="$BASE_DIR/$dbname/table.hql"
	TBL_TMP_FILE="$BASE_DIR/$dbname/table.tmp"
	echo "[$TBL_HQL_FILE]"
	echo "use $dbname;" > $TBL_HQL_FILE
	while read tblname
	do
		echo "desc formatted ${tblname};" >> $TBL_HQL_FILE
	done < $LST_TABLE
	hive -S -v -f $TBL_HQL_FILE > $TBL_TMP_FILE
	
	#テーブル情報個別ファイル出力
	LST_DESC=""
	while read line
	do
		if [ "`echo $line | grep '^desc formatted'`" != "" ];then
			tblname=`echo $line | awk '{print $3}'`
			if [ "$tblname" != "" ];then
				LST_DESC="$BASE_DIR/$dbname/table/${tblname}.dat"
				echo "[$LST_DESC]"
				cp /dev/null $LST_DESC
			fi
			continue
		fi
		if [ "$LST_DESC" == "" ];then
			continue
		fi
		echo $line >> $LST_DESC
	done < $TBL_TMP_FILE

	#パーティション情報取得
	PRT_HQL_FILE="$BASE_DIR/$dbname/partition.hql"
	PRT_TMP_FILE="$BASE_DIR/$dbname/partition.tmp"
	echo "[$PRT_HQL_FILE]"
	echo "use $dbname;" > $PRT_HQL_FILE
	tblname=""
	while read line
	do
		if [ "`echo $line | grep '^desc formatted'`" != "" ];then
			tblname=`echo $line | awk '{print $3}'`
			continue
		fi
		if [ "$tblname" == "" ];then
			continue
		fi
		if [ "`echo $line | grep '^# Partition Information'`" != "" ];then
			echo "show partitions $tblname;" >> $PRT_HQL_FILE
			tblname=""
			continue
		fi
	done < $TBL_TMP_FILE
	hive -S -v -f $PRT_HQL_FILE > $PRT_TMP_FILE 

	#パーティション情報個別ファイル出力
	LST_PART=""
	while read line
	do
		if [ "`echo $line | grep '^show partitions'`" != "" ];then
			tblname=`echo $line | awk '{print $3}'`
			if [ "$tblname" != "" ];then
				LST_PART="$BASE_DIR/$dbname/partition/${tblname}.dat"
				echo "[$LST_PART]"
				cp /dev/null $LST_PART
			fi
			continue
		fi
		if [ "$LST_PART" == "" ];then
			continue
		fi
		echo $line >> $LST_PART
	done < $PRT_TMP_FILE
	
done < $LST_DATABASE

exit 0
