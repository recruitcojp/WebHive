<?php
class DatauploadsController extends AppController {
	var $name = 'datauploads';
	var $components = array('Auth','Common');
	var $helpers = array('Html', 'Form', 'Javascript');
	var $user;
	
	function index() {
		$this->layout = "dataupload";
	}

	///////////////////////////////////////////////////////////////////
	//ファイルアップロード
	///////////////////////////////////////////////////////////////////
	function fileupload() {

		//$this->log($this->params,LOG_DEBUG);
		$this->layout = "ajax";

		//異常チェック
		if ( empty($this->params['form']['DataUploadText']) or empty($this->params['form']['DataUploadUserid'])){
			$this->set("result" , array("success" => false,'msg'=>'データが未入力です。'));
			return;
		}
		if ( $this->params['form']['DataUploadText']=="" or $this->params['form']['DataUploadUserid']=="" ){
			$this->set("result" , array("success" => false,'msg'=>'データが未入力です。'));
			return;
		}

		if ( HIVE_DATABASE_UPLOAD == "" ){
			$this->set("result" , array("success" => false,'msg'=>'データップロード機能は利用できません。'));
			return;
		}

		//各種パラメータ設定
		$u_arr=split("\n",$this->params['form']['DataUploadText']);
		$u_userid=$this->params['form']['DataUploadUserid'];
		$u_id=sprintf("%s_%05d",date("YmdHis"),getmypid());
		$u_database=HIVE_DATABASE_UPLOAD;
		$u_table="${u_userid}_${u_id}";
		$tmp_dir = DIR_UPLOAD . "/$u_userid/${u_id}";
		$tmp_file = DIR_UPLOAD . "/${u_id}.txt";
		$hql_file = DIR_REQUEST . "/$u_userid/${u_id}.hql";

		//一時ファイル名
		if ( CommonComponent::MakeDirectory($u_userid,$u_id) != 0 ){
			$this->set("result" , array("success" => false,'msg'=>'ディレクトリ作成に失敗しました。'));
			return;
		}

		//クエリ実行履歴出力
		$u_query="use $u_database;\nLOAD DATA LOCAL INPATH '$tmp_file' OVERWRITE INTO TABLE $u_table;\n";
		$runlog['Runhists']['username']=$u_userid;
		$runlog['Runhists']['hive_database']=$u_database;
		$runlog['Runhists']['query']=$u_query;
		$runlog['Runhists']['rid']=$u_id;
		$runlog['Runhists']['rsts']=0;
		//$this->log($runlog,LOG_DEBUG);
		if ( !($this->Runhists->save($runlog, array('username','hive_database','query','rid','rsts') )) ){
			$this->set("result" , array("success" => false,'msg'=>'db access error'));
			return;
		}
		$runlog['Runhists']['id'] = $this->Runhists->getLastInsertID();

		//一時ファイル出力
		$column_cnt=0;
		$line_cnt=0;
		if ( !($fp=fopen($tmp_file,"w")) ){
			$this->set("result" , array("success" => false,'msg'=>'file open error'));
			CommonComponent::UpdateRunhistsResult($u_id,513);
			return;
		}
		for($i=0; $i<count($u_arr); $i++){
			//カラム数
			if ( $i == 0 ){
				$lines=split("\t",$u_arr[$i]);
				$column_cnt=count($lines);
			}

			fputs($fp,$u_arr[$i] . "\n");
			$line_cnt++;
		}
		fclose($fp);
		$this->log("$tmp_file => LINE=$line_cnt COLUMN=$column_cnt",LOG_DEBUG);

		//一時テーブル作成クエリ
		if ( !($fp=fopen($hql_file,"w")) ){
			$this->set("result" , array("success" => false,'msg'=>'file open error'));
			CommonComponent::UpdateRunhistsResult($u_id,514);
			return;
		}
		fputs($fp,"use $u_database;");
		fputs($fp,"create table $u_table (");
		for($i=1; $i<=$column_cnt; $i++){
			if ( $i == 1 ){
				$wk=sprintf("c%03d string",$i);
			}else{
				$wk=sprintf(",c%03d string",$i);
			}
			fputs($fp,$wk);
		}
		fputs($fp,") ROW FORMAT DELIMITED FIELDS TERMINATED BY '\t' LINES TERMINATED BY '\n' STORED AS TEXTFILE;");
		fclose($fp);

		//一時テーブル作成
		$cmd=CMD_HIVE." -f $hql_file";
		$this->log("CMD=$cmd",LOG_DEBUG);
		exec("$cmd 2>&1",$result,$retval);
		$this->log("CMD=$cmd => $retval",LOG_DEBUG);
		if ( $retval != 0 ){
			$this->set("result" , array("success" => false,'msg'=>"一時テーブルの作成に失敗しました。"));
			CommonComponent::UpdateRunhistsResult($u_id,511);
			return;
		}

		//HiveQLファイル作成(監査ログ)
		if ( !($fp=fopen($hql_file,"w")) ){
			$this->set("result" , array("success" => false,'msg'=>'file open error'));
			CommonComponent::UpdateRunhistsResult($u_id,514);
			return;
		}
		fputs($fp,"$u_query");
		fclose($fp);

		//Load DATAの実行
		$cmd=CMD_HIVE." -f $hql_file";
		$this->log("CMD=$cmd",LOG_DEBUG);
		exec("$cmd > /dev/null 2>&1",$result,$retval);
		$this->log("CMD=$cmd => $retval",LOG_DEBUG);
		if ( $retval != 0 ){
			$this->set("result" , array("success" => false,'msg'=>"DATA LOADが失敗しました。"));
			CommonComponent::UpdateRunhistsResult($u_id,516);
			return;
		}

		CommonComponent::UpdateRunhistsResult($u_id,200);
		$msg="データ投入が正常終了しました。<br><br>${u_database}.${u_table}<br>";
		$this->set("result" , array("success" => true, 'msg'=>"$msg"));
	}

	function beforeFilter() {
		$this->loadModel('Runhists');
	}

	function beforeRender() {
	}
}
?>
