var sv_reqid='';
var sv_timerid='';
var sv_db='';
var sv_cmp='';
var sv_col='';
var sv_sql='';
var sv_func='';
var sv_str='';

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
	Ext.Msg.alert("リクエストエラー", "サーバリクエストが異常終了しました。").setIcon(Ext.Msg.ERROR);
}

/////////////////////////////////////////////////////////
//プログレスバー設定
/////////////////////////////////////////////////////////
function SetProgress(stage,map,reduce) {
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
	sv_sql=sv_sql.replace(sv_str,text);
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
function HiveExplain(inDB, inSql,inCmp,inCol) {
	if ( inSql == null ) { return; }
	if ( sv_timerid != '' ){
		TextOutFunc("WAR:HiveQL Running");
		return;
	}
	sv_db=inDB;
	sv_sql=inSql;
	sv_cmp=inCmp;
	sv_col=inCol;
	sv_func='explain';
	CheckHiveQL();
}

function HiveExplain_req() {

	Ext.Ajax.request({
		url:'/WebHive/apis/explain',
		method:'POST',
		params:{
			u:userid,
			d:sv_db,
			z:sv_cmp,
			c:sv_col,
			q:sv_sql
		},
		success:HiveExplain_fin,
		failure:AjaxRequestFail
	});

}

function HiveExplain_fin(result,opt) {
	var res = Ext.decode(result.responseText);
	if ( res.result == "unknown" || res.result == "check" || res.result == "ok" ){
		sv_reqid=res.id;
		HiveDownload("exp");
		return;
	}
	TextOutFunc("ERR:HiveQL Explain error(" + res.result + ")");
}

/////////////////////////////////////////////////////////
// HiveQL実行
/////////////////////////////////////////////////////////
function HiveExecute(inDB,inSql,inCmp,inCol) {
	if ( inSql == null ) { return; }
	if ( sv_timerid != '' ){
		TextOutFunc("WAR:HiveQL Running");
		return;
	}
	sv_db=inDB;
	sv_sql=inSql;
	sv_cmp=inCmp;
	sv_col=inCol;
	sv_func='execute';
	CheckHiveQL();
}

function HiveExecute_req() {

	Ext.Ajax.request({
		url:'/WebHive/apis/explain',
		method:'POST',
		params:{
			u:userid,
			d:sv_db,
			z:sv_cmp,
			c:sv_col,
			q:sv_sql
		},
		success:HiveExecute_fin,
		failure:AjaxRequestFail
	});

	Ext.getCmp("outTab").setActiveTab("outConsole");
	Ext.getCmp("outExplain").setValue("");
	Ext.getCmp("outDataView").setValue("");
	Ext.getDom("outConsole").innerHTML = "";
	TextOutFunc("INF:HiveQL Request start");
	SetProgress(0,0,0);
}

function HiveExecute_fin(result,opt) {
	var res = Ext.decode(result.responseText);

	if ( res.result == "unknown" ){
		Ext.Msg.confirm('クエリ実行確認','HiveQLを実行しますか？' ,function(btn){
			if(btn == 'yes'){ HiveRequest(res.id); }
		});
		return;
	}
	if ( res.result == "check" ){
		Ext.Msg.confirm('クエリ実行確認','HiveQLを実行しますか？<br>' + res.msg ,function(btn){
			if(btn == 'yes'){ HiveRequest(res.id); }
		});
		return;
	}
	if ( res.result == "ok" ){
		HiveRequest(res.id);
		return;
	}
	TextOutFunc("ERR:HiveQL Request error(" + res.result + ")");
	SetProgress(100,100,100);
}

/////////////////////////////////////////////////////////
// Hiveリクエスト発行
/////////////////////////////////////////////////////////
function HiveRequest(id) {

	Ext.Ajax.request({
		url:'/WebHive/apis/request',
		method:'POST',
		params:{
			u:userid,
			d:sv_db,
			z:sv_cmp,
			c:sv_col,
			id:id
		},
		success:HiveRequest_fin,
		failure:AjaxRequestFail
	});

	SetProgress(1,0,0);
	sv_timerid=1;
}

function HiveRequest_fin(result,opt) {
	var res = Ext.decode(result.responseText);

	if ( res.result == "ok" ){
		TextOutFunc("INF:HiveQL Request-ID：" + res.id);
		TextOutFunc("INF:<a href=\"javascript:void(0);\" onclick=\"HiveJobCancel()\">HiveQL Request Cancel<a>");
		sv_timerid=setTimeout("HiveProcCheck(\"" + res.id + "\")",5000);
		sv_reqid=res.id;
		return;
	}
	if ( res.result == "fin" ){
		TextOutFunc("INF:HiveQL normal end");
		msg="Result Download (" + res.filnm + ")"; 
		TextOutFunc("INF:<a href=\"/WebHive/result/" + userid + "/" + res.filnm + "\" target=\"_blank\">" + msg + "<a>");
		SetProgress(100,100,100);
		sv_reqid=res.id;
		sv_timerid='';
		return;
	}

	TextOutFunc("ERR:HiveQL Request error(" + res.result + ")");
	SetProgress(100,100,100);
	sv_timerid='';
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

	if ( res.result == "ok" ){
		TextOutFunc("INF:HiveQL normal end");
		msg="Result Download (" + res.filnm + ")"; 
		TextOutFunc("INF:<a href=\"/WebHive/result/" + userid + "/" + res.filnm + "\" target=\"_blank\">" + msg + "<a>");
		SetProgress(100,100,100);
		sv_timerid='';
		return;
	}
	if ( res.result == "progress" ){
		SetProgress(res.stage,res.map,res.reduce);
		if ( sv_timerid != "" ){
			sv_timerid=setTimeout("HiveProcCheck(\"" + res.id + "\")",5000);
		}
		return;
	}

	TextOutFunc("ERR:HiveQL execute error(" + res.result + ")");
	SetProgress(100,100,100);
	sv_timerid='';
}

/////////////////////////////////////////////////////////
//ジョブ中断処理
/////////////////////////////////////////////////////////
function HiveJobCancel() {
	if ( sv_timerid == "" ){ return; }

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
	clearTimeout(sv_timerid);
	sv_timerid='';
}

function HiveJobCancel_fin(result,opt) {
	var res = Ext.decode(result.responseText);
	TextOutFunc("INF:HiveQL Job Cancel (" + res.result + ")");
	SetProgress(100,100,100);
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

