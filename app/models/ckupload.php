<?php
class Ckupload extends AppModel {
	var $name = 'Ckupload';
	var $useTable = false;

	//データ入力チェック（バリデーション）
	var $validate = array(
	'outdir' => array(
		array('rule' => 'notEmpty','message' => '出力先が指定されていません。','last' => true),
		array('rule' => array('between', 1, 100),'message' => '出力先は100文字まで指定可能です。','last' => true),
	),
	'filename' => array(
		array('rule' => 'notEmpty','message' => 'ファイル名が指定されていません。','last' => true),
	),
	'filenm' => array(
		array('rule' => 'isUploadedFile','message' => 'ファイルのアップロードでエラーが発生しました。','last' => true),
	),
	);

	function isUploadedFile($params){
		$val = array_shift($params);
		if ( !isset($val['error']) or $val['error'] != 0 ){
			return false;
		}
		if ( empty($val['tmp_name']) or $val['tmp_name'] == '' ){
			return false;
		}
		if ( empty($val['size']) or $val['size'] <= 0 ){
			return false;
		}
		if ( !is_uploaded_file($val['tmp_name']) ){
			return false;
		}
		return true;
	}

}
?>
