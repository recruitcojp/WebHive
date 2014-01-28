var sv_reqid='';
var sv_timerid='';
var sv_db='';
var sv_sql='';
var sv_func='';
var sv_str='';
var job_cancel_flg=0;

Ext.Ajax.timeout = <?php echo CLIENT_TIMEOUT ?>;

/////////////////////////////////////////////////////////
// Windowを閉じる時の終了確認
/////////////////////////////////////////////////////////
window.onbeforeunload = function(event) {
	if ( sv_timerid != "" ){
		event = event || window.event;
		event.returnValue = '実行中のJOBがあります。\nこのページを終了する前に、実行中のJOBを終了してください。\n[キャンセル]をクリックして、現在のページに戻ってください。';
	}
}

/////////////////////////////////////////////////////////
// Ajaxリクエスト失敗時の処理
/////////////////////////////////////////////////////////
function AjaxRequestFail(request,opt) {
	ButtonControll("enable");
	Ext.Msg.alert("リクエストエラー", "サーバリクエストが異常終了しました。").setIcon(Ext.Msg.ERROR);
}

/////////////////////////////////////////////////////////
//プログレスバー設定
/////////////////////////////////////////////////////////
function SetProgress(total,stage,map,reduce) {
	Ext.getCmp("inTotalProgress").updateProgress(total/100, total + '%');
	Ext.getCmp("inStageProgress").updateProgress(stage/100, stage + '%');
	Ext.getCmp("inMapProgress").updateProgress(map/100, map + '%');
	Ext.getCmp("inRedProgress").updateProgress(reduce/100, reduce + '%');
}

/////////////////////////////////////////////////////////
//クエリ中の円マーク($)で囲まれた文字を置換し、置換文字列がなくなったらリクエストを発行
/////////////////////////////////////////////////////////
function CheckHiveQL(){
	var deli="$";
	var deli_len=deli.length;

	pos1=sv_sql.indexOf(deli);
	if ( pos1 == -1 ){
		if ( sv_func == 'explain' ){ HiveExplain_req(); }
		if ( sv_func == 'execute' ){ HiveExecute_req(); }
		return;
	}

	pos2=sv_sql.indexOf(deli,pos1+deli_len);
	if ( pos2 == -1 ){
		if ( sv_func == 'explain' ){ HiveExplain_req(); }
		if ( sv_func == 'execute' ){ HiveExecute_req(); }
		return;
	}

	sv_str=sv_sql.substring(pos1,pos2+deli_len);
	if ( sv_str.length <= (deli_len * 2) ){
		Ext.Msg.alert("HiveQLエラー", "置換文字列指定が不正です。クエリを見直して下さい。").setIcon(Ext.Msg.WARNING);
		return;
	}

	Ext.MessageBox.prompt("HiveQL置換", "「"+sv_str+"」の置換文字列を指定してください。", CheckHiveQL_fin);
}

function CheckHiveQL_fin(btn,text){
	if (btn != "ok") { return; }
	sv_sql=sv_sql.split(sv_str).join(text);
	
	CheckHiveQL();
}

/////////////////////////////////////////////////////////
// HiveQL登録
/////////////////////////////////////////////////////////
function HiveRegister(inQid,inTitle,inSql) {
	if ( inTitle == null ) { return; }
	if ( inSql == null ) { return; }

	Ext.Ajax.request({
		url:'/WebHive/apis/register',
		method:'POST',
		params:{
			u:userid,
			i:inQid,
			t:inTitle,
			q:inSql
		},
		success:HiveRegister_fin,
		failure:AjaxRequestFail
	});
	TextOutFunc("INF:HiveQL Register");
}

function HiveRegister_fin(result,opt) {
	var res = Ext.decode(result.responseText);
	var obj = new MyEvent();

	if ( res.result == "ok" ){
		TextOutFunc("INF:HiveQL Register OK(ID=" + res.qid + ")");
		obj.fireEvent('HiveReloadEvent');
		if ( res.qid != "" ){
			Ext.getCmp("inQid").setValue(res.qid);
		}
	}else{
		TextOutFunc("ERR:HiveQL Register error(" + res.result + ")");
	}

}

/////////////////////////////////////////////////////////
// HiveQL削除
/////////////////////////////////////////////////////////
function HiveDelete(sqlID) {
	if ( sqlID == null ) { return; }

	Ext.Ajax.request({
		url:'/WebHive/apis/delete',
		method:'POST',
		params:{
			u:userid,
			id:sqlID
		},
		success:HiveDelete_fin,
		failure:AjaxRequestFail
	});
	TextOutFunc("INF:HiveQL delete (ID=" + sqlID + ")");
}

function HiveDelete_fin(result,opt) {
	var res = Ext.decode(result.responseText);
	var obj = new MyEvent();

	if ( res.result == "ok" ){
		TextOutFunc("INF:HiveQL Delete OK");
		obj.fireEvent('HiveReloadEvent');
	}else{
		TextOutFunc("ERR:HiveQL Delete error(" + res.result + ")");
	}

}

/////////////////////////////////////////////////////////
// HiveQLのexplain
/////////////////////////////////////////////////////////
function HiveExplain(inDB, inSql) {
	if ( inSql == null ) { return; }
	if ( sv_timerid != '' ){
		TextOutFunc("WAR:HiveQL Running");
		return;
	}
	sv_db=inDB;
	sv_sql=inSql;
	sv_func='explain';
	CheckHiveQL();
}

function HiveExplain_req() {

	ButtonControll("disable");
	TextOutFunc("INF:HiveQL Check start");
	sv_reqid='';

	Ext.Ajax.request({
		url:'/WebHive/apis/explain',
		method:'POST',
		params:{
			u:userid,
			d:sv_db,
			q:sv_sql
		},
		success:HiveExplain_fin,
		failure:AjaxRequestFail
	});

}

function HiveExplain_fin(result,opt) {
	ButtonControll("enable");
	var res = Ext.decode(result.responseText);
	if ( res.result == "unknown" || res.result == "check" || res.result == "ok" ){
		sv_reqid=res.id;
		TextOutFunc("INF:HiveQL Check OK");
		HiveDownload("exp");
		return;
	}
	if ( res.result == "query error" ){
		sv_reqid=res.id;
		TextOutFunc("ERR:HiveQL Check error");
		HiveDownload("exp");
		return;
	}
	TextOutFunc("ERR:HiveQL Check error(" + res.result + ")");
}

/////////////////////////////////////////////////////////
// HiveQL実行
/////////////////////////////////////////////////////////
function HiveExecute(inDB,inSql) {
	if ( inSql == null ) { return; }
	if ( sv_timerid != '' ){
		TextOutFunc("WAR:HiveQL Running");
		return;
	}
	sv_db=inDB;
	sv_sql=inSql;
	sv_func='execute';
	CheckHiveQL();
}

function HiveExecute_req() {

	ButtonControll("disable");
	Ext.getCmp("outTab").setActiveTab("outConsole");
	Ext.getCmp("outExplain").setValue("");
	Ext.getCmp("outOutput").setValue("");
	Ext.getCmp("outDataView").setValue("");
	Ext.getDom("outConsole").innerHTML = "";
	SetProgress(0,0,0,0);
	sv_reqid='';

	TextOutFunc("INF:HiveQL Check start");
	Ext.Ajax.request({
		url:'/WebHive/apis/explain',
		method:'POST',
		params:{
			u:userid,
			d:sv_db,
			q:sv_sql
		},
		success:HiveExecute_fin,
		failure:AjaxRequestFail
	});

}

function HiveExecute_fin(result,opt) {

	var res = Ext.decode(result.responseText);

	if ( res.result == "unknown" ){
		TextOutFunc("INF:HiveQL Check OK");
		Ext.Msg.confirm('クエリ実行確認','HiveQLを実行しますか？' ,function(btn){
			if(btn == 'yes'){
				HiveRequest(res.id); 
			}else{
				TextOutFunc("INF:HiveQL Request Cancel");
				HiveRequestFinish();
			}
		});
		return;
	}
	if ( res.result == "check" ){
		TextOutFunc("INF:HiveQL Check OK");
		Ext.Msg.confirm('クエリ実行確認','HiveQLを実行しますか？<br>' + res.msg ,function(btn){
			if(btn == 'yes'){
				HiveRequest(res.id); 
			}else{
				TextOutFunc("INF:HiveQL Request Cancel");
				HiveRequestFinish();
			}
		});
		return;
	}
	if ( res.result == "ok" ){
		HiveRequest(res.id);
		return;
	}
	if ( res.result == "query error" ){
		sv_reqid=res.id;
		TextOutFunc("ERR:HiveQL Check error");
		HiveDownload("exp");
		HiveRequestFinish();
		return;
	}
	TextOutFunc("ERR:HiveQL Check error(" + res.result + ")");
	HiveRequestFinish();
}

/////////////////////////////////////////////////////////
// Hiveリクエスト発行
/////////////////////////////////////////////////////////
function HiveRequest(id) {

	TextOutFunc("INF:HiveQL Request start");

	Ext.Ajax.request({
		url:'/WebHive/apis/request',
		method:'POST',
		params:{
			u:userid,
			d:sv_db,
			id:id
		},
		success:HiveRequest_fin,
		failure:AjaxRequestFail
	});

	SetProgress(0,0,0,0);
	sv_timerid=1;
}

function HiveRequest_fin(result,opt) {
	var res = Ext.decode(result.responseText);

	if ( res.result == "ok" ){
		TextOutFunc("INF:HiveQL Request-ID：" + res.id);
		TextOutFunc("INF:<a href=\"javascript:void(0);\" onclick=\"HiveJobCancel()\">HiveQL Request Cancel<a>");
		sv_timerid=setTimeout("HiveProcCheck(\"" + res.id + "\")",2000);
		sv_reqid=res.id;
	}else{
		TextOutFunc("ERR:HiveQL Request error(" + res.result + ")");
		HiveRequestFinish();
		sv_timerid='';
	}
}

/////////////////////////////////////////////////////////
// Hiveリクエスト完了処理
/////////////////////////////////////////////////////////
function HiveRequestFinish(){
	job_cancel_flg=0;
	SetProgress(100,100,100,100);
	ButtonControll("enable");
}

/////////////////////////////////////////////////////////
// Hive処理状況確認
/////////////////////////////////////////////////////////
function HiveProcCheck(id) {
	Ext.Ajax.request({
		url:'/WebHive/apis/check',
		method:'POST',
		params:{
			u:userid,
			id:id
		},
		success:HiveProcCheck_fin,
		failure:AjaxRequestFail
	});
}

function HiveProcCheck_fin(result,opt) {
	var res = Ext.decode(result.responseText);

	if ( res.result == "progress" ){
		SetProgress(res.total, res.stage, res.map, res.reduce);
		if ( sv_timerid != "" ){
			sv_timerid=setTimeout("HiveProcCheck(\"" + res.id + "\")",5000);
		}
		return;
	}

	if ( res.result == "ok" ){
		filnm=res.filnm;
		filnms = filnm.split(',');
		for( i=0 ; i<filnms.length ; i++ ) {
			msg="Result Download (" + filnms[i] + ")"; 
			TextOutFunc("INF:<a href=\"/WebHive/result/download/" + userid + "/" + filnms[i] + "\" target=\"_blank\">" + msg + "<a>");
		}
		TextOutFunc("INF:HiveQL normal end");
		HiveRequestFinish();
		sv_timerid='';
		return ;
	}

	if ( res.result == "warning" ){
		filnm=res.filnm;
		filnms = filnm.split(',');
		for( i=0 ; i<filnms.length ; i++ ) {
			msg="Result Download (" + filnms[i] + ")"; 
			TextOutFunc("INF:<a href=\"/WebHive/result/download/" + userid + "/" + filnms[i] + "\" target=\"_blank\">" + msg + "<a>");
		}
		TextOutFunc("WAR:file size limit");
		HiveRequestFinish();
		sv_timerid='';
		return ;
	}

	TextOutFunc("ERR:HiveQL execute error(" + res.result + ")");
	HiveRequestFinish();
	sv_timerid='';
}

/////////////////////////////////////////////////////////
//ジョブ中断処理
/////////////////////////////////////////////////////////
function HiveJobCancel() {
	if ( sv_timerid == "" ){ return; }
	if ( job_cancel_flg == 1 ){ return; }
	job_cancel_flg=1;

	Ext.Ajax.request({
		url:'/WebHive/apis/jobcancel',
		method:'POST',
		params:{
			u:userid,
			id:sv_reqid
		},
		success:HiveJobCancel_fin,
		failure:AjaxRequestFail
	});

	TextOutFunc("INF:HiveQL Job Cancel (ID=" + sv_reqid + ")");
}

function HiveJobCancel_fin(result,opt) {
	var res = Ext.decode(result.responseText);
	TextOutFunc("INF:HiveQL Job Cancel (" + res.result + ")");
	job_cancel_flg=0;
	if ( res.result != "ok" ){ return; }
	HiveRequestFinish();
}

/////////////////////////////////////////////////////////
// クリアー
/////////////////////////////////////////////////////////
function HiveProcCheck_Clear() {
	if ( sv_timerid == "" ){ return; }
	clearTimeout(sv_timerid);
	sv_timerid='';
}


/////////////////////////////////////////////////////////
// Hive処理結果ダウンロード
/////////////////////////////////////////////////////////
function HiveDownload(dtype) {
	if (  sv_reqid == "" ){ return; }

	Ext.Ajax.request({
		url:'/WebHive/apis/download',
		method:'POST',
		params:{
			u:userid,
			id:sv_reqid,
			d:dtype
		},
		success:HiveDownload_fin,
		failure:AjaxRequestFail
	});
}

function HiveDownload_fin(result,opt) {
	var res = Ext.decode(result.responseText);

	if ( res.result != "ok" ){ return; }

	var msg="";
	for (var i = 0; i < res.datas.length; i++) {
		msg += res.datas[i].data + "\n";
	}

	if ( res.dtype == "exp" ){
		Ext.getCmp("outTab").setActiveTab("outExplain");
		Ext.getCmp("outExplain").setValue(msg);
	}else if ( res.dtype == "out" ){
		Ext.getCmp("outTab").setActiveTab("outOutput");
		Ext.getCmp("outOutput").setValue(msg);
	}else{
		Ext.getCmp("outTab").setActiveTab("outDataView");
		Ext.getCmp("outDataView").setValue(msg);
	}
}

/////////////////////////////////////////////////////////
//コンソールにメッセージを出力
/////////////////////////////////////////////////////////
function TextOutFunc(msg) {
	var richText=Ext.getDom("outConsole").innerHTML;
	var now = new Date();
	var watch1 = now.toLocaleString();

	if ( msg == "" ){
		richText += "<br>";
		Ext.getDom("outConsole").innerHTML = richText;
	}else{
		richText += watch1 + " " + msg + "<br>";
		Ext.getDom("outConsole").innerHTML = richText;
	}
}

/////////////////////////////////////////////////////////
//ボタンを有効／無効にする
/////////////////////////////////////////////////////////
function ButtonControll(ctl) {

        if ( ctl == "disable" ){
                Ext.getCmp('btnRun').disable();
                Ext.getCmp('btnExplain').disable();
<?php
if ( $auth_flg['data_upload'] == 1 ){
echo "
                Ext.getCmp('btnDataUpload').disable();
";
}
?>
        }

        if ( ctl == "enable" ){
                Ext.getCmp('btnRun').enable();
                Ext.getCmp('btnExplain').enable();
<?php
if ( $auth_flg['data_upload'] == 1 ){
echo "
                Ext.getCmp('btnDataUpload').enable();
";
}
?>
        }
}

