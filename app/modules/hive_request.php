<?php

///////////////////////////////////////////////////////////////////
// 初期化処理
///////////////////////////////////////////////////////////////////
//共通定義
define('ROOT', dirname(dirname(__FILE__)));
require_once(ROOT."/config/app.php");

//ダミー
class Configure {
function write($config, $value = null) {}
}

//ファイルアーカイブ用
set_include_path(get_include_path() .PATH_SEPARATOR. DIR_ARCH_LIB); 
require_once "File/Archive.php";
require_once "common/query_parser.php";

//LANG設定
putenv("LANG=".APP_LANG);

// 引数チェック
if ( empty($argv[1]) or empty($argv[2]) ){
	print "ERR:parameter error\n";
	exit(1);
}
$hive_uid=$argv[1];
$hive_id=$argv[2];

//ファイル名
$hql_file = DIR_REQUEST."/${hive_uid}/${hive_id}.hql";
$fin_file = DIR_RESULT."/${hive_uid}/${hive_id}.fin";
$pid_file = DIR_RESULT."/${hive_uid}/${hive_id}.pid";
$out_file = DIR_RESULT."/${hive_uid}/${hive_id}.out";

///////////////////////////////////////////////////////////////////
//pidファイル作成
///////////////////////////////////////////////////////////////////
$mypid=getmypid();
if ( !($fp=fopen($pid_file,"w")) ){
	WriteFinFile($fin_file,"ERR:file open error($pid_file)\n");
	exit(1);
}
$ret=fputs($fp,"$mypid\n");
fclose($fp);

///////////////////////////////////////////////////////////////////
// クエリ解析
///////////////////////////////////////////////////////////////////
$OUTPUT_VERBOSE="";
$OUTPUT_HEADER="";
$OUTPUT_RECSEPCHAR="\t";
if ( !($ifp=fopen($hql_file,"r")) ){
	WriteFinFile($fin_file,"ERR:open($hql_file) error\n");
	unlink($pid_file);
	exit(1);
}
while(!feof($ifp)){
	$row = fgets($ifp,102400);
	if ( eregi('^--WEBHIVE VERBOSE',$row) ){ $OUTPUT_VERBOSE="V"; }
	if ( eregi('^--WEBHIVE HEADER',$row) ){ $OUTPUT_HEADER="H"; }
	if ( eregi('^--WEBHIVE RECSEPCHAR',$row) ){
		$row=str_replace(array("\r\n","\n","\r"), '', $row);
		$arr=preg_split("/ /",$row);
		$OUTPUT_RECSEPCHAR=$arr[2];
	}
}
fclose($ifp);


///////////////////////////////////////////////////////////////////
// クエリ実行
///////////////////////////////////////////////////////////////////
$nowdate=date("Y/m/d g:i:s");
WriteFinFile($fin_file,"START:$nowdate\n");

$sv_db="";
$sv_zip_file="";
$file_cnt=0;
$file_size=0;
$sv_query="";
if ( $OUTPUT_VERBOSE == "" and $OUTPUT_HEADER == "" ){
	$cmd = CMD_HIVE . " -f $hql_file 2>$out_file";
}else{
	$cmd = CMD_HIVE . " -v -f $hql_file 2>$out_file";
}
if ( !($ifp=popen($cmd,"r")) ){
	WriteFinFile($fin_file,"ERR:popen($cmd) error\n");
	unlink($pid_file);
	exit(1);
}
while(!feof($ifp)){
	$row = fgets($ifp,102400);

	//カラムヘッダ制御
	if ( $OUTPUT_HEADER != "" ){
		if ( eregi('^use',$row) ){
			$arr=preg_split("/[ \n]/",$row);
			$sv_db=$arr[1];
		}
		if ( eregi('^--select',$row) ){
			$sv_query=substr($row,2);
			continue;
		}
		if ( $sv_query != "" and  !eregi('^select',$row) ){
			$sv_query=query_parser::get_header($sv_db,$sv_query,$OUTPUT_RECSEPCHAR,$row);
			$row="$sv_query\n$row";
			$sv_query="";
		}
	}

	//TAB変換処理
	if ( $OUTPUT_RECSEPCHAR != "\t" ){
		$row=str_replace(array("\t"), $OUTPUT_RECSEPCHAR, $row);
	}

	//出力ファイル
	$row_len=strlen($row);
	if ( ($row_len + $file_size) > OUTPUT_FILE_MAX ){
		$file_size=0;
		$file_cnt++;
	}
	if ( $file_cnt >= OUTPUT_FILE_LIMIT ){
		WriteFinFile($fin_file,"WAR:file size limit over\n");
		break;
	}
	$file_size += $row_len;
	$zip_file=sprintf("%s/%s/%s_%03d.zip",DIR_RESULT,$hive_uid,$hive_id,$file_cnt);
	$zip_file_in=sprintf("%s_%03d.csv",$hive_id,$file_cnt);

	//出力ファイルのオープン
	if ( $sv_zip_file != $zip_file ){
		if ( $sv_zip_file != "" ){
			WriteFinFile($fin_file,"OUT:$sv_zip_file\n");
			$ofp->close();
		}
		$ofp = File_Archive::toArchive($zip_file, File_Archive::toFiles(),"zip");
		$ofp->newFile($zip_file_in);
	}
	$sv_zip_file=$zip_file;

	//出力
	$row=mb_convert_encoding($row,"SJIS","UTF-8");
	$ofp->writeData($row);
}
pclose($ifp);

if ( $sv_zip_file != "" ){
	WriteFinFile($fin_file,"OUT:$sv_zip_file\n");
	$ofp->close();
}

//処理完了ファイルのクローズ
$nowdate=date("Y/m/d g:i:s");
WriteFinFile($fin_file,"END:$nowdate\n");

///////////////////////////////////////////////////////////////////
//終了処理
///////////////////////////////////////////////////////////////////
unlink($pid_file);

exit(0);

///////////////////////////////////////////////////////////////////
//FINファイル出力
///////////////////////////////////////////////////////////////////
function WriteFinFile($fin_file,$msg) {
	if ( !($rfp=fopen($fin_file,"a")) ){
		print "ERR:file open error($fin_file)\n";
		return 1;
	}
	$ret=fputs($rfp,"$msg");
	fclose($rfp);
	return 0;
}

?>
