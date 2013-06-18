<?php

class query_parser {

function get_header($p_db,$p_query,$p_sep,$p_row){

	if ( !eregi('^select',$p_query) ){ return ""; }

	//select～from間の文字列を取り出す
	$ck_flg=0;
	$wk_hd="";
	$arr=preg_split("/[ \n]/",$p_query);
	for ($i=1; $i<count($arr); $i++){
		if ( eregi('from',$arr[$i]) ){ $ck_flg=1; break; }
		if ( $wk_hd != "" ){ $wk_hd.=" "; }
		$wk_hd.="$arr[$i]";
	}
	if ( $ck_flg == 0 ){ return ""; }

	//関数内の文字を削除
	$wk_hd=preg_replace("/\((.+?)\)/","()",$wk_hd);

	//別名置換
	$wk_hd2="";
	$arr=preg_split("/,/",$wk_hd);
	for ($i=0; $i<count($arr); $i++){
		$last_col="";
		$arr2=preg_split("/ /",$arr[$i]);
		for ($j=0; $j<count($arr2); $j++){
			if ( $arr2[$j] == "" ){ continue; }
			$last_col=$arr2[$j];
		}
		if ( $wk_hd2 != "" ){ $wk_hd2.=$p_sep; }
		$wk_hd2.=$last_col;
	}

	//アスタリスクが含まれる場合は出力データからカラム名を生成
	if ( !eregi('\*',$wk_hd2) ){ return $wk_hd2; }

	$wk_hd2="";
	$arr=preg_split("/[ \n]/",$p_row);
	for ( $i=0; $i<count($arr); $i++){
		if ( $wk_hd2 != "" ){ $wk_hd2.=$p_sep; }
		$wk_hd2.=sprintf("col%d",$i+1);
	}

	return "$wk_hd2";
}

}

?>
