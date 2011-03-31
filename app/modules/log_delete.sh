#!/bin/bash

#######################################################################
#初期処理
#######################################################################
KEEP_DAY=3					#ログ保存期間
LOG_DIR="/var/www/html/WebHive"			#ログ保存ディレクトリ
LOG_FILE="$LOG_DIR/app/tmp/logs/log_delete.log"	#スクリプトログ

EMPURGE_DSK_THR=70				#緊急パージ（ディスク使用率）
EMPURGE_DSK_OVR=1048576				#緊急パージ（削除サイズ）

#######################################################################
# 関数名：print_msg
#######################################################################
print_msg(){
	echo "`date '+%Y/%m/%d %H:%M:%S'` $1" | tee -a $LOG_FILE
}
print_msg "INF:log_delete.sh start"

#######################################################################
# 関数名：log_delete
#######################################################################
log_delete(){
	log_path=$1
	log_keep=$2
	log_ptn=$3

	print_msg "INF:log_delete($log_path,$log_keep,$log_ptn)"
	find ${log_path} -mtime +${log_keep} -name "${log_ptn}" -print | while read fil
	do
		print_msg "INF:rm $fil"
		rm $fil
	done
}

#######################################################################
# 関数名：log_delete_em
#######################################################################
log_delete_em(){
	log_path=$1
	log_size=$2
	log_ptn=$3

	print_msg "INF:log_delete_em($log_path,$log_size,$log_ptn)"
	find ${log_path} -size +${log_size}k -name "${log_ptn}" -print | while read fil
	do
		print_msg "INF:rm $fil"
		rm $fil
	done
}

#######################################################################
#通常パージ
#######################################################################
log_delete ${LOG_DIR}/request ${KEEP_DAY} "*.hql"
log_delete ${LOG_DIR}/request ${KEEP_DAY} "*.cmp"
log_delete ${LOG_DIR}/result  ${KEEP_DAY} "*.out"
log_delete ${LOG_DIR}/result  ${KEEP_DAY} "*.csv"
log_delete ${LOG_DIR}/result  ${KEEP_DAY} "*.pid"
log_delete ${LOG_DIR}/result  ${KEEP_DAY} "*.tmp"
log_delete ${LOG_DIR}/result  ${KEEP_DAY} "*.exp"
log_delete ${LOG_DIR}/result  ${KEEP_DAY} "*.zip"

#######################################################################
#緊急パージ
#######################################################################
dsk_used=`df $LOG_DIR | grep -v Filesystem | grep % | sed 's/[%\/]//g' | awk '{print $NF}'`
print_msg "INF:ディスク使用率=${dsk_used}% (THR=${EMPURGE_DSK_THR}%)"
if (( ${dsk_used} >= ${EMPURGE_DSK_THR} )); then
	print_msg "INF:緊急パージ処理(${EMPURGE_DSK_OVR}KB以上のログを削除します)"

	log_delete_em ${LOG_DIR}/result ${EMPURGE_DSK_OVR} "*.csv"
	log_delete_em ${LOG_DIR}/result ${EMPURGE_DSK_OVR} "*.zip"
	log_delete_em ${LOG_DIR}/result ${EMPURGE_DSK_OVR} "*.tmp"
fi

print_msg "INF:log_delete.sh end"
exit 0
