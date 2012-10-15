Ext.Loader.setConfig({enabled: true});
Ext.Loader.setPath('Ext.ux', '/WebHive/ext/ux/');

Ext.require([
	'Ext.form.field.ComboBox',
	'Ext.container.*',
	'Ext.grid.*',
	'Ext.data.*',
	'Ext.util.*',
	'Ext.ProgressBar.*',
	'Ext.state.*',
	'Ext.window.Window',
	'Ext.ux.FieldReplicator'
]);

Ext.onReady(function() {

	///////////////////////////////////////////////////////////////////
	//カスタムイベント
	///////////////////////////////////////////////////////////////////
	MyEvent = Ext.extend(Ext.util.Observable, {
		constructor: function() {
			this.addEvents('HiveReloadEvent');
			this.on('HiveReloadEvent', function() {
				storeSQL.load({params:{u:userid,q:'my'}});
			});
		}
	});

	///////////////////////////////////////////////////////////////////
	//データストア
	///////////////////////////////////////////////////////////////////
	//データストアの設定(登録クエリ/実行履歴/クエリ変更履歴)
	var storeSQL = Ext.create('Ext.data.Store', {
		proxy: {
			type: 'ajax',
			actionMethods : 'POST',
			url:'/WebHive/apis/select',
			totalProperty:'total',
			reader: {
				type: 'json',
				root:'row'
			}
		},
		fields: [
			{name: 'id' },
			{name: 'username' },
			{name: 'title' },
			{name: 'sql' },
			{name: 'rid' },
			{name: 'rfil' },
			{name: 'rsts' },
			{name: 'created' }
		],
		autoLoad:false
	});
	storeSQL.load({params:{u:userid,q:'my'}});

	//データストアの設定(利用可能データベース)
	var storeDatabase = Ext.create('Ext.data.Store', {
		proxy: {
			type: 'ajax',
			actionMethods : 'POST',
			url:'/WebHive/apis/database',
			totalProperty:'total',
			reader: {
				type: 'json',
				root:'row'
			}
		},
		fields: [
			{name: 'id'},
			{name: 'caption'}
		],
		autoLoad:false
	});
	storeDatabase.load({ params:{ u:userid } });

	///////////////////////////////////////////////////////////////////
	// グリッドパネル
	///////////////////////////////////////////////////////////////////
	var selModel = Ext.create('Ext.selection.RowModel', {
		singleSelect:true
	});

	var gridPanel = Ext.create('Ext.grid.Panel', {
		id:'gridPanel',
		height: '100%',
		width: '100%',
		selModel: selModel,
		autoExpandColumn:'title',
		store: storeSQL,
		title: config.ui.titleSelect,
		columns: [{
			id : 'id',
			header	: 'QUERY ID',
			dataIndex: 'id',
			hidden	: true
		},{
			id	: 'rid',
			header	: 'REQUEST ID',
			dataIndex: 'rid',
			hidden	: true
		},{
			id	: 'rfil',
			header	: 'RUN ID',
			dataIndex: 'rfil',
			hidden	: true
		},{
			id	: 'created',
			header	: 'DATE',
			dataIndex: 'created',
			hidden	: false
		},{
			id	: 'username',
			header	: 'USERNAME',
			dataIndex: 'username',
			hidden	: false
		},{
			id	: 'title',
			header	: 'TITLE',
			dataIndex: 'title',
			hidden	: false
		},{
			id	: 'sql',
			header	: 'HiveQL',
			dataIndex: 'sql',
			hidden	: false
		},{
			id	: 'rsts',
			header	: 'STATUS',
			dataIndex: 'rsts',
			hidden	: true
		}],
		tbar:[ {
			text:'全登録クエリ表示',
			iconCls:'preview-hide',
			handler: function(btn){
				storeSQL.load({params:{u:userid,q:'all'}});
				ChangeGridColumn('all');
			}
		},{
			text:'マイクエリ表示',
			iconCls:'details',
			handler:function(btn){
				storeSQL.load({params:{u:userid,q:'my'}});
				ChangeGridColumn('my');
			}
		},{
			text:'実行履歴表示',
			iconCls:'preview-bottom',
			handler:function(btn){
				storeSQL.load({params:{u:userid,q:'history'}});
				ChangeGridColumn('history');
			}
		},{
			text:'クエリ変更履歴表示',
			iconCls:'preview-right',
			handler:function(btn){
				storeSQL.load({params:{u:userid,q:'mod'}});
				ChangeGridColumn('mod');
			}
		}]
	});

	///////////////////////////////////////////////////////////////////
	// クエリ入力
	///////////////////////////////////////////////////////////////////
	var inputQuery = Ext.create('Ext.Panel', {
		xtype:'form',
		layout: 'column',
		border: false,
		width: 550,
		items:[{
<?php 
if ( $user_auth==1 ){ echo "
			id: 'inCreDatabase',
			width: 400,
			xtype:'form',
			layout: 'column',
			border: false,
			items: [
				{id:'inCreDBtitle', name:'inCreDBtitle', xtype:'displayfield', fieldLabel: 'Create Database'},
				{id:'inCreDBname',  name:'inCreDBname',  xtype:'textfield'},
				{id:'btnCreDB',  name:'btnCreDB',  xtype:'button', text:config.ui.btnCreDB, width:100 }
			]
		},{
";
}
?>
			id: 'inDatabase',
			xtype: 'combo',
			store: storeDatabase,
			fieldLabel: 'Database',
			value:'default',
			width: 300,
			editable: false,
			triggerAction: 'all',
			mode: 'local',
			valueField: "id",
			displayField: "caption",
			queryMode: 'local',
			typeAhead: true
		},{
			id: 'inQid',
			xtype: 'textfield',
			hidden:true,
			readOnly: true,
			fieldLabel: 'Query ID'
		},{
			id: 'inTitle',
			xtype: 'textfield',
			<?php if ( $user_auth==3 ){ echo "readOnly: true,\n"; } ?>
			width: 500,
			fieldLabel: 'Title'
		},{
			id: 'inHiveQL',
			xtype: 'textarea',
			<?php if ( $user_auth==3 ){ echo "readOnly: true,\n"; } ?>
			width: 500,
			height: 125,
			fieldLabel: 'HiveQL'
		},{
			id: 'inQueryButton',
			xtype: 'button',
			iconCls:'query-button',
			margin: '0 0 0 3',
			listeners:{
				click:  function(button,event){
					HiveQL = Ext.getCmp('inHiveQL').getValue();
					Ext.getCmp('HiveQLMessage').setValue(HiveQL);
					QueryForm.show();
				}
			}
		}]
	});

	///////////////////////////////////////////////////////////////////
	// 入力ボタン
	///////////////////////////////////////////////////////////////////
	var inputButton = Ext.create('Ext.Panel', {
		xtype:'form',
		layout: 'column',
		border: false,
		width: 550,
		buttons:[{
			xtype: 'button',
			id:'btnRun',
			minWidth: 70,
			//margin: '5 3 3 3',
			text: config.ui.btnRun
		},{
			xtype: 'button',
			id:'btnExplain',
			minWidth: 70,
			//margin: '5 3 3 3',
			text: config.ui.btnExplain
<?php 
if ( $user_auth==1 or $user_auth==2){ echo "
		},{
			xtype: 'button',
			id:'btnReg',
			minWidth: 70,
			//margin: '5 3 3 3',
			text: config.ui.btnReg
";
}
if ( $user_auth==1 ){ echo "
		},{
			xtype: 'button',
			id:'btnSql',
			minWidth: 70,
			//margin: '5 3 3 3',
			text: config.ui.btnSql
";
}
if ( $user_auth==1 and $upload_flg==1 ){ echo "
		},{
			xtype: 'button',
			id:'btnUpload',
			minWidth: 70,
			//margin: '5 3 3 3',
			text: config.ui.btnUpload
";
}
?>
		},{
			xtype: 'button',
			id:'btnReset',
			minWidth: 70,
			//margin: '5 3 3 3',
			text: config.ui.btnReset
		}]
	});

	///////////////////////////////////////////////////////////////////
	// Hive進捗状況
	///////////////////////////////////////////////////////////////////
	var inputProgress = Ext.create('Ext.Panel', {
		xtype:'form',
		layout: 'column',
		border: false,
		margin: '5 5 5 5',
		width: 530,
		items:[{
			id: 'inTotalProgressForm',
			width: 200,
			xtype:'form',
			layout: 'column',
			border: false,
			items: [
				{id:'txtTotalProgress', name:'txtTotalProgress', xtype:'displayfield', width:40, fieldLabel: 'Total'},
				{id:'inTotalProgress',  name:'inTotalProgress',  xtype:'progressbar', width:140, text:'0%'}
			]
		},{
			id: 'inStageProgressForm',
			width: 110,
			xtype:'form',
			layout: 'column',
			border: false,
			items: [
				{id:'txtStageProgress', name:'txtStageProgress', xtype:'displayfield', width:40, fieldLabel: 'Stage'},
				{id:'inStageProgress',  name:'inStageProgress',  xtype:'progressbar', width:50, text:'0%'}
			]
		},{
			id: 'inMapProgressForm',
			width: 110,
			xtype:'form',
			layout: 'column',
			border: false,
			items: [
				{id:'txtMapProgress', name:'txtMapProgress', xtype:'displayfield', width:40, fieldLabel: 'Map'},
				{id:'inMapProgress',  name:'inMapProgress',  xtype:'progressbar', width:50, text:'0%'}
			]
		},{
			id: 'inRedProgressForm',
			width: 110,
			xtype:'form',
			layout: 'column',
			border: false,
			items: [
				{id:'txtRedProgress', name:'txtRedProgress', xtype:'displayfield', width:50, fieldLabel: 'Reduce'},
				{id:'inRedProgress',  name:'inRedProgress',  xtype:'progressbar', width:50, text:'0%'}
			]
		}]
	});


	///////////////////////////////////////////////////////////////////
	// HiveQL入力パネル
	///////////////////////////////////////////////////////////////////
	var inputPanel = Ext.create('Ext.Panel', {
		height: '100%',
		width: '100%',
		id:'inTextarea',
		bodyStyle: 'padding:5px',
		title: config.ui.titleInput,
		xtype:'form',
		style: 'font-family: \"Courier New\",Courier,monospace;font-weight:normal;',
		defaultType: 'textfield',
		items: [{
			id: 'inputQuery',
			xtype:'fieldset',
			layout: 'column',
			title: 'Input',
			border: false,
			width: 550,
			height: 230,
			items:inputQuery
		},{
			id: 'inProgress',
			xtype:'fieldset',
			layout: 'column',
			title: 'Progress',
			width: 550,
			height: 50,
			items:inputProgress
		},{
			id: 'inputButton',
			xtype:'form',
			layout: 'column',
			border: false,
			width: 550,
			height: 50,
			items:inputButton
		}]
	});

	///////////////////////////////////////////////////////////////////
	// 下部パネル
	///////////////////////////////////////////////////////////////////
	var outputPanel = Ext.create('Ext.Panel', {
		items: [{
		id: 'outTab',
		xtype: 'tabpanel',
		deferredRender: true,
		activeTab: 0,
		style: 'font-family: \"Courier New\",Courier,monospace;font-weight:normal;',
		width: '100%',
		height: 250,
		items:[{
			id:'outConsole',
			title:'Console',
			layout:'fit',
			preventBodyReset: true,
			style: 'font-size:11px;',
			listeners: {activate: handleActivate},
			autoScroll: true,
			xtype:'box',
			autoScroll: true
		},{
			id:'outExplain',
			title:'Explain',
			layout:'fit',
			listeners: {activate: handleActivate},
			xtype:'textarea',
			readOnly: true,
			height: '100%',
			border: 'none'
		},{
			id:'outOutput',
			title:'Output',
			layout:'fit',
			listeners: {activate: handleActivate},
			xtype:'textarea',
			readOnly: true,
			height: '100%',
			border: 'none'
		},{
			id:'outDataView',
			title:'Data View',
			layout:'fit',
			listeners: {activate: handleActivate},
			xtype:'textarea',
			readOnly: true,
			height: '100%',
			border: 'none'
		}]
		}]
	});

	///////////////////////////////////////////////////////////////////
	// Viewport設定
	///////////////////////////////////////////////////////////////////
	Ext.create('Ext.container.Viewport', {
		layout: 'border',
		renderTo: Ext.getBody(),
		items:[{
			items: gridPanel,
			region: 'west',
			split: true,
			height: '60%',
			width: '50%'
		},{
			items: inputPanel,
			region: 'center',
			split: true,
			height: '60%',
			width: '50%'
		},{
			items: outputPanel,
			region: 'south',
			split: true,
			height: '40%',
			width: '100%'
		}]
	});

	gridPanel.columns[6].setSize(300);

	///////////////////////////////////////////////////////////////////
	// グリッドダブルクリック
	///////////////////////////////////////////////////////////////////
	gridPanel.on('itemdblclick', function(view, record, item, index, e, eopts) {
		e.stopEvent();
		handleSelect(record);
	});

<?php
if ( $user_auth==1 or $user_auth==2){ echo "
	///////////////////////////////////////////////////////////////////
	// グリッドの右クリック
	///////////////////////////////////////////////////////////////////
	gridPanel.on('itemcontextmenu', function(view, record, item, index, e, eopts) {
		e.stopEvent();
		handleDelete(record);
	});	
";
}
?>

<?php 
if ( $user_auth==1 ){ echo "
	///////////////////////////////////////////////////////////////////
	//DBスキーマ追加
	///////////////////////////////////////////////////////////////////
	Ext.get('btnCreDB').on('click', function() {
		if ( sv_timerid != '' ){
			TextOutFunc('WAR:HiveQL Running');
			return;
		}
		inCreDBname=Ext.getCmp('inCreDBname').getValue();
		if ( inCreDBname.trim() == '' ) {
			Ext.Msg.alert(config.msg.checkInput, config.msg.emptyDBname);
			return;
		}
		Ext.Msg.confirm(config.msg.checkCreDB, '「' + inCreDBname + '」を作成しますか？' ,function(btn){
			if(btn == 'yes'){
				HiveCreDB(inCreDBname);
			}
		});
	});
";
} 
?>

<?php 
if ( $user_auth==1 and $upload_flg==1 ){ echo "
	///////////////////////////////////////////////////////////////////
	//ファイルアップロード
	///////////////////////////////////////////////////////////////////
	Ext.get('btnUpload').on('click', function() {
		window.open('/WebHive/uploads', '', 'width=550,height=250');
	});
";
} 
?>

<?php 
if ( $user_auth==1 ){ echo "
	///////////////////////////////////////////////////////////////////
	//SQL管理画面ボタンクリック時の処理
	///////////////////////////////////////////////////////////////////
	Ext.get('btnSql').on('click', function() {
		window.open('/WebHive/hiveqls', '', 'width=900,height=500,scrollbars=yes');
	});
";
} 
?>

<?php 
if ( $user_auth==1 or $user_auth==2){ echo "
	///////////////////////////////////////////////////////////////////
	//登録ボタンクリック時の処理
	///////////////////////////////////////////////////////////////////
	Ext.get('btnReg').on('click', function() {
		inQid = Ext.getCmp('inQid').getValue();
		inTitle = Ext.getCmp('inTitle').getValue();
		inHiveQL = Ext.getCmp('inHiveQL').getValue();
		if ( inTitle.trim() == '' ) {
			Ext.Msg.alert(config.msg.checkInput, config.msg.emptyTitle);
			return;
		}
		if (inHiveQL.trim() == '' ){
			Ext.Msg.alert(config.msg.checkInput, config.msg.emptyQuery);
			return;
		}
		var result = HiveRegister(inQid,inTitle,inHiveQL);
	});
";
} 
?>

	///////////////////////////////////////////////////////////////////
	//実行ボタンクリック時の処理
	///////////////////////////////////////////////////////////////////
	Ext.get('btnRun').on('click', function() {
		if ( sv_timerid != '' ){
			TextOutFunc('WAR:HiveQL Running');
			return;
		}
		inDB = Ext.getCmp('inDatabase').getRawValue();
		inSQL = Ext.getCmp('inHiveQL').getValue();
		if (inSQL.trim() == '') {
			Ext.Msg.alert(config.msg.checkInput, config.msg.emptyQuery);
		} else {
			var result = HiveExecute(inDB,inSQL);
		}
	});

	///////////////////////////////////////////////////////////////////
	//Explainボタンクリック時の処理
	///////////////////////////////////////////////////////////////////
	Ext.get('btnExplain').on('click', function() {
		if ( sv_timerid != '' ){
			TextOutFunc('WAR:HiveQL Running');
			return;
		}

		inDB = Ext.getCmp('inDatabase').getRawValue();
		inSQL = Ext.getCmp('inHiveQL').getValue();
		if (inSQL.trim() == '') {
			Ext.Msg.alert(config.msg.checkInput, config.msg.emptyQuery);
		} else {
			var result = HiveExplain(inDB,inSQL);
		}
	});


	///////////////////////////////////////////////////////////////////
	//リセットボタンクリック時の処理
	///////////////////////////////////////////////////////////////////
	Ext.get('btnReset').on('click', function() {
		if ( sv_timerid != '' ){
			TextOutFunc('WAR:HiveQL Running');
			return;
		}

		Ext.getCmp('btnRun').enable();
		Ext.getCmp('btnExplain').enable();
		Ext.getCmp('outTab').setActiveTab('outConsole');
		Ext.getCmp('outExplain').setValue('');
		Ext.getCmp('outOutput').setValue('');
		Ext.getCmp('outDataView').setValue('');
		Ext.getDom('outConsole').innerHTML = '';
		Ext.getCmp('inQid').setValue('');
		Ext.getCmp('inTitle').setValue('');
		Ext.getCmp('inHiveQL').setValue('');
		SetProgress(0,0,0,0);
		HiveProcCheck_Clear();
		storeSQL.load({params:{u:userid,q:'my'}});

		sv_reqid='';
		sv_timerid='';
		sv_db='';
		sv_sql='';
		sv_func='';
		sv_str='';
	});


	///////////////////////////////////////////////////////////////////
	// クエリ入力画面
	///////////////////////////////////////////////////////////////////
	var QueryPanel = Ext.create('Ext.form.Panel', {
		plain: true,
		border: 0,
		bodyPadding: 5,
		layout: {
			type: 'vbox',
			align: 'stretch'
		},
		items: [{
			xtype: 'textarea',
			fieldLabel: 'HiveQL Input',
			hideLabel: true,
			id:'HiveQLMessage',
			name: 'HiveQLMessage',
			style: 'margin:0',
			flex: 1
		}]
	});

	var QueryForm = Ext.create('Ext.window.Window', {
		title: 'HiveQL Input',
		//collapsible: true,
		animCollapse: true,
		closable: false,
		maximizable: true,
		width: 750,
		height: 500,
		minWidth: 300,
		minHeight: 200,
		layout: 'fit',
		items: QueryPanel,
		dockedItems: [{
			xtype: 'toolbar',
			dock: 'bottom',
			ui: 'footer',
			layout: {
				pack: 'center'
			},
			items: [{
				minWidth: 80,
				text: 'Commit',
				listeners:{
					click: function(field){
						HiveQL = Ext.getCmp('HiveQLMessage').getValue();
						Ext.getCmp('inHiveQL').setValue(HiveQL);
						QueryForm.hide();
					}
				}
			},{
				minWidth: 80,
				text: 'Cancel',
				listeners:{
					click: function(field){
						QueryForm.hide();
					}
				}
			}]
		}]
	});

	///////////////////////////////////////////////////////////////////
	//SQL選択画面のダブルクリック
	///////////////////////////////////////////////////////////////////
	function handleSelect(record) {
		if(!record) {return;};
		if ( sv_timerid != '' ){
			TextOutFunc('WAR:HiveQL Running');
			return;
		}

		//HiveQL入力画面設定
		var id = new Ext.XTemplate('{id}').apply(record.data);
		var title = new Ext.XTemplate('{title}').apply(record.data);
		var sql = new Ext.XTemplate('{sql}').apply(record.data);
		Ext.getCmp('inQid').setValue(id);
		Ext.getCmp('inTitle').setValue(title);
		Ext.getCmp('inHiveQL').setValue(sql);

		//下部コンソール表示
		var rfil=record.get('rfil');
		if ( rfil != '' ){
			filnms = rfil.split(',');
			for( i=0 ; i<filnms.length ; i++ ) {
				msg='Result Download (' + filnms[i] + ')';
				TextOutFunc('INF:<a href="/WebHive/result/' + userid + '/' + filnms[i] + '" target="_blank">' + msg + '<a>');
			}
		}
		sv_reqid=record.get('rid');
	};

	///////////////////////////////////////////////////////////////////
	//タブ選択時の処理
	///////////////////////////////////////////////////////////////////
	function handleActivate(tab){
		if (tab.id == 'outExplain') { HiveDownload('exp'); }
		if (tab.id == 'outOutput') { HiveDownload('out'); }
		if (tab.id == 'outDataView') { HiveDownload('csv'); }
	}

	///////////////////////////////////////////////////////////////////
	//グリッド表示カラム変更
	///////////////////////////////////////////////////////////////////
	function ChangeGridColumn(typ){
		if ( typ == 'history' ){
			gridPanel.columns[5].setVisible(false);
			gridPanel.columns[7].setVisible(true);
		}else{
			gridPanel.columns[5].setVisible(true);
			gridPanel.columns[7].setVisible(false);
		}
	}

	///////////////////////////////////////////////////////////////////
	//登録クエリの削除
	///////////////////////////////////////////////////////////////////
	function handleDelete(record){
		if(!record) {return;};
		var qid=record.get('id');
		if ( qid == '' ){ return; }
		Ext.Msg.confirm(config.msg.checkDelete, '「' + record.get('title') + '」を削除しますか？' ,function(btn){
			if(btn == 'yes'){
				HiveDelete(qid);
				storeSQL.load({params:{u:userid,q:'my'}});
			}
		});
	}

	///////////////////////////////////////////////////////////////////
	// DB新規作成
	///////////////////////////////////////////////////////////////////
	function HiveCreDB(DBname) {
		if ( DBname == null ) { return; }

		Ext.Ajax.request({
			url:'/WebHive/apis/credb',
			method:'POST',
			params:{
				u:userid,
				name:DBname
			},
			success:HiveCreDB_fin,
			failure:AjaxRequestFail
		});
		TextOutFunc("INF:Create Database Request");
	}

	function HiveCreDB_fin(result,opt) {
		var res = Ext.decode(result.responseText);
		var obj = new MyEvent();

		if ( res.result == "ok" ){
			TextOutFunc("INF:Create Database OK");
			storeDatabase.add({id:inCreDBname, caption:inCreDBname});
		}else{
			TextOutFunc("ERR:Create Database error(" + res.result + ")");
		}
	}

});
