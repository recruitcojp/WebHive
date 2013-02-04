<?php
class EntityController extends AppController {
	var $name = 'Entity';
	var $components = array('RequestHandler','Auth');
	var $user;

	function index() {

		//////////////////////////////////////////////////////////////////
		//変数初期化
		//////////////////////////////////////////////////////////////////
		$this->layout='entity';
		$p_rows="";
		$p_data="";
		$p_database="";
		$p_table="";
		$p_data_t="";
		$p_data_p="";
		$p_data_i="";
		$db_database="";
		$db_owner="";
		$par_flg=0;
		$table_cnt=0;
		$line_flg=0;
		$line_cnt=0;

		//////////////////////////////////////////////////////////////////
		//パラメータ解析
		//////////////////////////////////////////////////////////////////
		if ( isset($this->params['url']['database_id']) ){
			$p_database_id=$this->params['url']['database_id'];
		}else{
			$p_database_id="default";
		}
		if ( isset($this->params['url']['table_id']) ){
			$p_table_id=$this->params['url']['table_id'];
		}else{
			$p_table_id="";
		}
		if ( isset($this->params['form']['filter']) ){
			$p_filter=$this->params['form']['filter'];
		}else{
			if ( isset($this->params['url']['filter']) ){
				$p_filter=$this->params['url']['filter'];
			}else{
				$p_filter="";
			}
		}
		if ( !file_exists(DIR_ENTITY."/data/${p_database_id}/table/${p_table_id}.dat") ){
			$p_table_id="";
		}

		//CLEARボタンクリック
		if ( isset($this->params['form']['clear_x']) or isset($this->params['form']['clear_y'])){
			$p_database_id="default";
			$p_table_id="";
			$p_filter="";
		}

		//GOボタンクリック
		if ( isset($this->params['form']['x']) or isset($this->params['form']['y'])){
			$p_table_id="";
		}

		//////////////////////////////////////////////////////////////////
		//テーブル一覧定義情報読込み
		//////////////////////////////////////////////////////////////////
		$fp = fopen(DIR_ENTITY."/conf/table.dat", "r");	
		while (($buffer = fgets($fp)) !== false) {
			$buffer = ereg_replace("\r|\n","",$buffer);
			if ( $buffer == "" ){ continue; }
			list($c1,$c2)=split('	',$buffer);
			if ( $c1 == "" ){ continue; }
			$c1=strtolower($c1);
			$table_data["$c1"]=$c2;
		}
		fclose($fp);

		//////////////////////////////////////////////////////////////////
		//テーブル定義情報読込み
		//////////////////////////////////////////////////////////////////
		$sv_table="";
		$fp = fopen(DIR_ENTITY."/conf/desc.dat", "r");	
		while (($buffer = fgets($fp)) !== false) {
			$buffer = ereg_replace("\r|\n","",$buffer);
			if ( $buffer == "" ){ continue; }
			list($c1,$c2,$c3,$c4)=split('	',$buffer);
			if ( $c1 != "" ){ $sv_table=$c1; }
			if ( $sv_table == "" ){ continue; }
			if ( $sv_table != $c1 ){ continue; }
			$col_data["$c2"]=$c3;
			$code_data["$c2"]=$c4;
		}
		fclose($fp);

		//////////////////////////////////////////////////////////////////
		//データベース一覧
		//////////////////////////////////////////////////////////////////
		$fp = fopen(DIR_ENTITY."/data/database.lst", "r");	
		while (($buffer = fgets($fp)) !== false) {
			$buffer = ereg_replace("\r|\n","",$buffer);
			if ( $buffer == "" ){ continue; }
			if ( $p_database_id == $buffer ){
				$p_database.="<option value='$buffer' selected>$buffer</option>";
			}else{
				$p_database.="<option value='$buffer'>$buffer</option>";
			}
		}
		fclose($fp);

		//////////////////////////////////////////////////////////////////
		//テーブル一覧
		//////////////////////////////////////////////////////////////////
		$p_data_i.="<br>";
		$p_data_i.="<table align=\"center\" bgcolor=\"#00438c\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\" width=\"100%\">\n";
		$p_data_i.="<tr bgcolor=\"#6d88ad\">";
		$p_data_i.="<td><strong><font color=\"#ffffff\">テーブル名</font></strong></td>";
		$p_data_i.="<td><strong><font color=\"#ffffff\">名称</font></strong></td>";
		$p_data_i.="</tr>";
		$p_table.="<option value=''> </option>";
		$fp = fopen(DIR_ENTITY."/data/$p_database_id/table.lst", "r");	
		while (($buffer = fgets($fp)) !== false) {
			$buffer = ereg_replace("\r|\n","",$buffer);
			if ( $buffer == "" ){ continue; }

			//テーブル名称
			if ( empty($table_data[$buffer]) ){
				$table_name="";
			}else{
				$table_name=$table_data[$buffer];
			}

			//フィルター
			if ( $p_filter != "" ){
				if ( preg_match("/$p_filter/i",$buffer)==false and preg_match("/$p_filter/i",$table_name)==false){ continue; }
			}

			//コンボボックス用
			if ( $p_table_id == $buffer ){
				$p_table.="<option value='$buffer' selected>$buffer</option>";
			}else{
				$p_table.="<option value='$buffer'>$buffer</option>";
			}

			//テーブル一覧表示用
			if ( ($table_cnt % 2) == 1 ){ $p_data_i.="<tr bgcolor=\"#f5f5f5\">"; }else{ $p_data_i.="<tr bgcolor=\"#e7e9f2\">"; }
			$p_data_i.="<td><a href=\"/WebHive/entity?database_id=${p_database_id}&table_id=${buffer}\">$buffer</a></td>";
			$p_data_i.="<td>${table_name}</td>";
			$p_data_i.="</tr>";
			$table_cnt++;
		}
		fclose($fp);
		$p_data_i.="</table>";
		$p_data_i.="<br>";

		//////////////////////////////////////////////////////////////////
		//テーブル構成
		//////////////////////////////////////////////////////////////////
		if ( $p_database_id != "" and $p_table_id != "" ){
			$p_data_t.="<br>";
			$p_data_t.="<div align='left'><b>テーブル構成情報</b></div>";
			$p_data_t.="<table align=\"center\" bgcolor=\"#00438c\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\" width=\"100%\">\n";
			$p_data_t.="<tr bgcolor=\"#6d88ad\">";
			$p_data_t.="<td><strong><font color=\"#ffffff\">カラム名</font></strong></td>";
			$p_data_t.="<td><strong><font color=\"#ffffff\">データ型</font></strong></td>";
			$p_data_t.="<td><strong><font color=\"#ffffff\">カラム名称</font></strong></td>";
			$p_data_t.="<td><strong><font color=\"#ffffff\">パーティション</font></strong></td>";
			$p_data_t.="<td><strong><font color=\"#ffffff\">備考</font></strong></td>";
			$p_data_t.="</tr>";
			$fp = fopen(DIR_ENTITY."/data/${p_database_id}/table/${p_table_id}.dat", "r");	
			while (($buffer = fgets($fp)) !== false) {
				$buffer = ereg_replace("\r|\n","",$buffer);
				if ( $buffer == "" ){ continue; }
		
				//テーブル情報
				if ( preg_match('/^Database:/',$buffer) ){ list($dummy,$db_database)=split('[	 ]+',$buffer); }
				if ( preg_match('/^Owner:/',$buffer) ){ list($dummy,$db_owner)=split('[	 ]+',$buffer); }
		
				//表示制御
				if ( preg_match('/^# Partition Information/',$buffer) ){ $par_flg=1; continue; }
				if ( preg_match('/^# col_name/',$buffer) ){ $line_flg++; continue; }
				if ( preg_match('/^#/',$buffer) ){ $line_flg=0; continue; }
				if ( $line_flg == 0 ){ continue; }
		
				//表示
				$buffers=split('[	 ]+',$buffer);
				if ( empty($buffers[0]) ){ continue; }
				if ( empty($col_data[$buffers[0]]) ){
					$entity_name="";
				}else{
					$entity_name=$col_data[$buffers[0]];
				}
				if ( empty($code_data[$buffers[0]]) ){
					$code_name="";
				}else{
					$code_name=$code_data[$buffers[0]];
				}
		
				if ( ($line_flg % 2) == 1 ){ $p_data_t.="<tr bgcolor=\"#f5f5f5\">"; }else{ $p_data_t.="<tr bgcolor=\"#e7e9f2\">"; }
				$p_data_t.="<td>$buffers[0]</td>";
				$p_data_t.="<td>$buffers[1]</td>";
				$p_data_t.="<td>$entity_name</td>";
				if ( $par_flg == 0 ){
					$p_data_t.="<td></td>";
				}else{
					$p_data_t.="<td>$par_flg</td>";
					$par_flg++;
				}
				$p_data_t.="<td>$code_name</td>";
				$p_data_t.="</tr>\n";
				$line_flg++;
				$line_cnt++;
			}
			fclose($fp);
			$p_data_t.="</table>\n";
			$p_data_t.="<br>";
		}

		//////////////////////////////////////////////////////////////////
		//テーブル名情報
		//////////////////////////////////////////////////////////////////
		if ( $p_table_id == "" ){
			$p_rows="テーブル一覧表示";
		}else{
			$p_rows="${db_database}.${p_table_id}";	
			if ( !empty($table_data[$p_table_id]) ){ 
				$p_rows.="(".$table_data[$p_table_id].")";
			}
		}

		//////////////////////////////////////////////////////////////////
		//パーティション情報
		//////////////////////////////////////////////////////////////////
		if ( $par_flg != 0 ){
			$p_data_p="<div align='left'><b>パーティション情報</b></div>";
			$p_data_p.="<table align=\"left\" bgcolor=\"#00438c\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\" width=\"50%\">\n";
			$p_data_p.="<tr bgcolor=\"#6d88ad\">";
			$p_data_p.="<td><strong><font color=\"#ffffff\">パーティション</font></strong></td>";
			$p_data_p.="</tr>";

			$fp = fopen(DIR_ENTITY."/data/$p_database_id/partition/${p_table_id}.dat", "r");	
			while (($buffer = fgets($fp)) !== false) {
				$buffer = ereg_replace("\r|\n","",$buffer);
				if ( $buffer == "" ){ continue; }
				if ( ($line_flg % 2) == 1 ){ $p_data_p.="<tr bgcolor=\"#f5f5f5\">"; }else{ $p_data_p.="<tr bgcolor=\"#e7e9f2\">"; }
				$p_data_p.="<td>$buffer</td>";
				$p_data_p.="</tr>\n";
				$line_flg++;
			}
			fclose($fp);

			$p_data_p.="</table>";
			$p_data_p.="<br><br>";
		}

		//////////////////////////////////////////////////////////////////
		//出力結果生成
		//////////////////////////////////////////////////////////////////
		if ( $line_cnt > 0 ){
			$p_data.=$p_data_t;
			$p_data.=$p_data_p;
		}else{
			if ( $table_cnt == 0 ){
				$p_data.="<br>テーブルが存在しません<br><br>";
			}else{
				$p_data.=$p_data_i;
			}
		}

		$para['database_id']=$p_database_id;
		$para['database']=$p_database;
		$para['table']=$p_table;
		$para['filter']=$p_filter;
		$para['previous']="";
		$para['rows']=$p_rows;
		$para['next']="";
		$para['data']=$p_data;
		$this->set('para', $para);
	}

	function download() {
		//////////////////////////////////////////////////////////////////
		//パラメータ
		//////////////////////////////////////////////////////////////////
		$col_data="";
		$code_data="";
		$p_data="";
		if ( isset($this->params['url']['database_id']) ){
			$p_database_id=$this->params['url']['database_id'];
		}else{
			$p_database_id="default";
		}

		//////////////////////////////////////////////////////////////////
		//テーブル定義情報
		//////////////////////////////////////////////////////////////////
		$sv_table="";
		$fp = fopen(DIR_ENTITY."/conf/desc.dat", "r");	
		while (($buffer = fgets($fp)) !== false) {
			$buffer = ereg_replace("\r|\n","",$buffer);
			if ( $buffer == "" ){ continue; }
			list($c1,$c2,$c3,$c4)=split('	',$buffer);
			if ( $c1 != "" ){ $sv_table=$c1; }
			if ( $sv_table == "" ){ continue; }
			$col_data["$sv_table"]["$c2"]=$c3;
			$code_data["$sv_table"]["$c2"]=$c4;
		}
		fclose($fp);

		//////////////////////////////////////////////////////////////////
		//テーブル構成
		//////////////////////////////////////////////////////////////////
		$p_data.="テーブル名,カラム名,データ型,カラム名称,パーティション,備考\n";
		$table_fp = fopen(DIR_ENTITY."/data/${p_database_id}/table.lst", "r");	
		while (($buffer = fgets($table_fp)) !== false) {
			$buffer = ereg_replace("\r|\n","",$buffer);
			if ( $buffer == "" ){ continue; }
			$p_data.=$this->table_disp($p_database_id,$buffer,$col_data,$code_data);
		}
		fclose($table_fp);

		//////////////////////////////////////////////////////////////////
		//CSV出力
		//////////////////////////////////////////////////////////////////
		$csv_file="${p_database_id}.csv";
		Configure::write('debug', 0);
		$this->autoRender=false;
		header ("Content-disposition: attachment; filename=" . $csv_file);
		header ("Content-type: application/octet-stream; name=" . $csv_file);

		$p_data = mb_convert_encoding($p_data, "SJIS", "UTF-8");
		print "$p_data";
	}

	function beforeFilter() {
	}

	function beforeRender() {
		$this->user=$this->Auth->user();
		$this->set('user', $this->user);
	}


	function table_disp($p_database_id,$p_table_id,$col_data,$code_data){
		$ret="";

		$par_flg=0;
		$col_flg=0;
		$fp = @fopen(DIR_ENTITY."/data/${p_database_id}/table/${p_table_id}.dat", "r");	
		if ( ! $fp ){ return ""; }
		while (($buffer = fgets($fp)) !== false) {
			$buffer = ereg_replace("\r|\n","",$buffer);
			if ( $buffer == "" ){ continue; }

			//制御
			if ( preg_match('/^# Partition Information/',$buffer) ){
				$par_flg=1; 
				continue;
			}
			if ( preg_match('/^# col_name/',$buffer) ){
				$col_flg++; 
				continue;
			}
			if ( preg_match('/^#/',$buffer) ){ $col_flg=0; continue; }
			if ( $col_flg == 0 ){ continue; }
	
			//表示
			$arr=split('[	 ]+',$buffer);
			$col_name=$arr[0];
			$data_type=$arr[1];
			if ( $col_name == "" ){ continue; }
			if ( empty($col_data[$p_table_id]) ){
				$entity_name="";
				$code_name="";
			}else{
				if ( empty($col_data[$p_table_id][$col_name]) ){
					$entity_name="";
				}else{
					$entity_name=$col_data[$p_table_id][$col_name];
				}
				if ( empty($code_data[$p_table_id][$col_name]) ){
					$code_name="";
				}else{
					$code_name=$code_data[$p_table_id][$col_name];
				}
			}

			$col_flg++;
			$ret.="$p_table_id,$col_name,$data_type,$entity_name,";
			if ( $par_flg != 0 ){
				$ret.="$par_flg";
				$par_flg++;
			}
			$ret.=",$code_name\n";
		}
		fclose($fp);
		return $ret;
	}


}
?>
