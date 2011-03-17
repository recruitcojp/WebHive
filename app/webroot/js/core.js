var sv_reqid='';
var sv_timerid='';

window.onbeforeunload = function(event) {
	if ( sv_timerid != "" ){
		event = event || window.event;
		event.returnValue = 'JOB実行中ですが、終了しますか？';
	}
}

/////////////////////////////////////////////////////////
// HiveQL登録
/////////////////////////////////////////////////////////
// リクエスト
function HiveRegister(targetTitle,targetSql) {
	if ( targetTitle == null ) { return; }
	if ( targetSql == null ) { return; }

	var parameter="u=" + userid + "&t=" + targetTitle + "&q=" + targetSql;
	var a = new Ajax.Request(
		"/WebHive/apis/register",
		{"method": "post", parameters:parameter, onComplete: HiveRegister_fin}
	);
	TextOutFunc("INF:HiveQL Register");
}

// 応答
function HiveRegister_fin(request) {
	var json = request.responseText;
	var result = eval("("+json+")");
	var res=result["result"];
	var obj = new MyEvent();

	if ( res == "ok" ){
		TextOutFunc("INF:HiveQL Register OK");
		obj.fireEvent('HiveReloadEvent');
	}else{
		TextOutFunc("ERR:HiveQL Register error(" + res + ")");
	}

}

/////////////////////////////////////////////////////////
// HiveQL削除
/////////////////////////////////////////////////////////
// リクエスト
function HiveDelete(sqlID) {
	if ( sqlID == null ) { return; }

	var parameter="u=" + userid + "&id=" + sqlID;
	var a = new Ajax.Request(
		"/WebHive/apis/delete",
		{"method": "post", parameters:parameter, onComplete: HiveDelete_fin}
	);
	TextOutFunc("INF:HiveQL delete (ID=" + sqlID + ")");
}

// 応答
function HiveDelete_fin(request) {
	var json = request.responseText;
	var result = eval("("+json+")");
	var res=result["result"];
	var obj = new MyEvent();

	if ( res == "ok" ){
		TextOutFunc("INF:HiveQL Delete OK");
		obj.fireEvent('HiveReloadEvent');
	}else{
		TextOutFunc("ERR:HiveQL Delete error(" + res + ")");
	}

}

/////////////////////////////////////////////////////////
// HiveQLのexplain
/////////////////////////////////////////////////////////
function HiveExplain(targetSql) {
	if ( targetSql == null ) { return; }

	if ( sv_timerid != '' ){
		TextOutFunc("WAR:HiveQL Running");
		return;
	}

	var parameter="u=" + userid + "&q=" + targetSql;
	var a = new Ajax.Request(
		"/WebHive/apis/explain",
		{"method": "post", parameters:parameter, onComplete:HiveExplain_fin}
	);
}

function HiveExplain_fin(request) {
	var json = request.responseText;
	var result = eval("("+json+")");
	var res=result["result"];
	var id=result["id"];

	if ( res == "unknown" || res == "check" || res == "ok" ){
		sv_reqid=id;
		HiveDownload("exp");
		return;
	}
	TextOutFunc("ERR:HiveQL Explain error(" + res + ")");
}

/////////////////////////////////////////////////////////
// HiveQL実行
/////////////////////////////////////////////////////////
// リクエスト
function HiveExecute(targetSql) {
	if ( targetSql == null ) { return; }

	if ( sv_timerid != '' ){
		TextOutFunc("WAR:HiveQL Running");
		return;
	}

	var parameter="u=" + userid + "&q=" + targetSql;
	var a = new Ajax.Request(
		"/WebHive/apis/explain",
		{"method": "post", parameters:parameter, onComplete:HiveExecute_fin}
	);
	Ext.getCmp("outTab").setActiveTab("outConsole");
	Ext.getCmp("outExplain").setValue("");
	Ext.getCmp("outDataView").setValue("");
	Ext.getDom("outConsole").innerHTML = "";
	TextOutFunc("INF:HiveQL Request start");
	SetProgress(0,0,0);
}

function HiveExecute_fin(request) {
	var json = request.responseText;
	var result = eval("("+json+")");
	var res=result["result"];
	var id=result["id"];

	if ( res == "unknown" ){
		var ck=window.confirm('HiveQLを実行しますか？')
		if ( ck == false ){ return; }
		HiveRequest(id);
		return;
	}
	if ( res == "check" ){
		var msg=result["msg"];
		var ck=window.confirm('HiveQLを実行しますか？\n' + msg)
		if ( ck == false ){ return; }
		HiveRequest(id);
		return;
	}
	if ( res == "ok" ){
		HiveRequest(id);
		return;
	}
	TextOutFunc("ERR:HiveQL Request error(" + res + ")");
	SetProgress(100,100,100);
}

/////////////////////////////////////////////////////////
// Hiveリクエスト発行
/////////////////////////////////////////////////////////
// リクエスト
function HiveRequest(id) {
	var parameter="u=" + userid + "&id=" + id;
	var a = new Ajax.Request(
		"/WebHive/apis/request",
		{"method": "post", parameters:parameter, onComplete:HiveRequest_fin}
	);
	SetProgress(1,0,0);
	sv_timerid=1;
}

// 応答
function HiveRequest_fin(request) {
	var json = request.responseText;
	var result = eval("("+json+")");
	var res=result["result"];
	var id=result["id"];

	if ( res == "ok" ){
		TextOutFunc("INF:HiveQL Request-ID：" + id);
		TextOutFunc("INF:<a href=\"javascript:void();\" onclick=\"HiveJobCancel()\">HiveQL Request Cancel<a>");
		sv_timerid=setTimeout("HiveProcCheck(\"" + id + "\")",5000);
		sv_reqid=id;
		return;
	}
	if ( res == "fin" ){
		TextOutFunc("INF:HiveQL normal end");
		TextOutFunc("INF:<a href=\"/WebHive/result/" + id + ".csv\" target=\"_blank\">HiveQL Result<a>");
		SetProgress(100,100,100);
		sv_reqid=id;
		sv_timerid='';
		return;
	}

	TextOutFunc("ERR:HiveQL Request error(" + res + ")");
	SetProgress(100,100,100);
	sv_timerid='';
}

/////////////////////////////////////////////////////////
// Hive処理状況確認
/////////////////////////////////////////////////////////
function HiveProcCheck(id) {
	var parameter="u=" + userid + "&id=" + id;
	var a = new Ajax.Request(
		"/WebHive/apis/check",
		{"method": "post", parameters:parameter, onComplete:HiveProcCheck_fin}
	);
}

// Hive処理完了チェック
function HiveProcCheck_fin(request) {
	var json = request.responseText;
	var result = eval("("+json+")");
	var res=result["result"];
	var id=result["id"];
	var stage=result["stage"];
	var map=result["map"];
	var reduce=result["reduce"];

	if ( res == "ok" ){
		TextOutFunc("INF:HiveQL normal end");
		TextOutFunc("INF:<a href=\"/WebHive/result/" + id + ".csv\" target=\"_blank\">HiveQL Result<a>");
		SetProgress(100,100,100);
		sv_timerid='';
		return;
	}
	if ( res == "progress" ){
		SetProgress(stage,map,reduce);
		if ( sv_timerid != "" ){
			sv_timerid=setTimeout("HiveProcCheck(\"" + id + "\")",5000);
		}
		return;
	}

	TextOutFunc("ERR:HiveQL execute error(" + res + ")");
	SetProgress(100,100,100);
	sv_timerid='';
}

/////////////////////////////////////////////////////////
//ジョブ中断処理
/////////////////////////////////////////////////////////
function HiveJobCancel() {
	if ( sv_timerid == "" ){ return; }
	var parameter="u=" + userid + "&id=" + sv_reqid;
	var a = new Ajax.Request(
		"/WebHive/apis/jobcancel",
		{"method": "post", parameters:parameter, onComplete:HiveJobCancel_fin}
	);
	TextOutFunc("INF:HiveQL Job Cancel (ID=" + sv_reqid + ")");
	clearTimeout(sv_timerid);
	sv_timerid='';
}

function HiveJobCancel_fin(request) {
	var json = request.responseText;
	var result = eval("("+json+")");
	var res=result["result"];
	TextOutFunc("INF:HiveQL Job Cancel (" + res + ")");
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
//リクエスト
function HiveDownload(dtype) {
	if (  sv_reqid == "" ){ return; }

	var parameter="u=" + userid + "&id=" + sv_reqid + "&d=" + dtype;
	var a = new Ajax.Request(
		"/WebHive/apis/download",
	   {"method": "post", parameters:parameter, onComplete:HiveDownload_fin}
	);

	var msg="Data Download(ID=" + sv_reqid + ")...";
	Ext.getCmp("outDataView").setValue(msg);
}

// 応答
function HiveDownload_fin(request) {
	var json = request.responseText;
	var result = eval("("+json+")");
	var res=result["result"];
	var dtype=result["dtype"];
	var datas=result["datas"];

	if ( res != "ok" ){
		Ext.getCmp("outTab").setActiveTab("outConsole");
		TextOutFunc("INF:Data Download error(" + res + ")");
		return;
	}

	var msg="";
	for (var i = 0; i < datas.length; i++) {
		msg += datas[i].data + "\n";
	}

	if ( dtype == "exp" ){
		Ext.getCmp("outTab").setActiveTab("outExplain");
		Ext.getCmp("outExplain").setValue(msg);
	}else if ( dtype == "out" ){
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
//プログレスバー設定
/////////////////////////////////////////////////////////
function SetProgress(stage,map,reduce) {
	Ext.getCmp("inStageProgress").updateProgress(stage/100);
	Ext.getCmp("inMapProgress").updateProgress(map/100);
	Ext.getCmp("inRedProgress").updateProgress(reduce/100);
}
