Ext.BLANK_IMAGE_URL = 'ext/resources/images/default/s.gif';

Ext.onReady(function() {

	///////////////////////////////////////////////////////////////////
	//カスタムイベント
	///////////////////////////////////////////////////////////////////
	MyEvent = Ext.extend(Ext.util.Observable, {
		constructor: function() {
			this.addEvents('HiveReloadEvent');
			this.on('HiveReloadEvent', function() {
				store.load();
			});
		}
	});

	///////////////////////////////////////////////////////////////////
	//Grid
	///////////////////////////////////////////////////////////////////
	//データストアの設定
	var store = new Ext.data.JsonStore({
		url:'/WebHive/apis/select',
		totalProperty:'total',
		root:'row',
		fields:[
			{name:'id'},
			{name:'title'},
			{name:'sql'},
		],
		autoLoad:true,
	});
	store.load();

	//SelModelの設定
	var sm = new Ext.grid.RowSelectionModel({
		singleSelect:true
	});

	//カラムモデルの設定
	var column = new Ext.grid.ColumnModel([ {
			id:'title',
			header:'TITLE',
			dataIndex:'title',
			width:100
		}, {
			id:'sql',
			header:'HiveQL',
			dataIndex:'sql',
			width:300
		}
	]);

	//グリッドパネルの作成
	var grid = new Ext.grid.GridPanel({
		id:'my-grid',
		autoExpandColumn:'title',
		height:210,
		width:400,
		cm:column,
		sm:sm,
		store:store,
		listeners:{
			rowdblclick:function(grid, row, e){
				var record = grid.getStore().getAt(row);
				handleSelect(record);
			},
			rowcontextmenu:function(grid, row, e){
				grid.getSelectionModel().selectRow(row);
				e.stopEvent();
				var record = grid.getStore().getAt(row);
				handleDelete(record);
			}
		}
	});

	///////////////////////////////////////////////////////////////////
	// HiveQL入力
	///////////////////////////////////////////////////////////////////
	var inputPanel = new Ext.Panel({
		layout:'fit',
		height: 320,
		layout:'border',
		renderTo: 'displayPanel',
		region: 'north',
		items: [{
			id: 'inSelect',
			title: config.ui.titleSelect,
			layout:'fit',
			region:'west',
			width: 500,
			items: grid,
			split: true,
		},{
			id:'inTextarea',
			bodyStyle: 'padding:15px',
			title: config.ui.titleInput,
			collapsible: false,
			region:'center',
			xtype:'form',
			style: 'font-family: \"Courier New\",Courier,monospace;font-weight:normal;',
			width: 500,
			labelWidth: 70,
			defaultType: 'textfield',
			split: true,
			items: [{
				id: 'inTitle',
				xtype: 'textfield',
				fieldLabel: 'Title',
				width: 500
			},{
				id: 'inHiveQL',
				xtype: 'textarea',
				fieldLabel: 'HiveQL',
				width: 500,
				height: 100
			},{
				id: 'inCompress',
				xtype: 'checkbox',
				fieldLabel: 'Compress',
				checked: true
			},{
				id: 'inStageProgress',
				xtype: 'progress',
				fieldLabel: 'Stage(%)',
				width: 500
			},{
				id: 'inMapProgress',
				xtype: 'progress',
				fieldLabel: 'Map(%)',
				width: 500
			},{
				id: 'inRedProgress',
				xtype: 'progress',
				fieldLabel: 'Reduce(%)',
				width: 500
			}],
		}],
		buttons: [{
			id:'btnReg',
			text: config.ui.btnReg
		},{
			id:'btnRun',
			text: config.ui.btnRun
		},{
			id:'btnExplain',
			text: config.ui.btnExplain
		},{
			id:'btnReset',
			text: config.ui.btnReset
		}],
	});

	///////////////////////////////////////////////////////////////////
	//下部のパネル
	///////////////////////////////////////////////////////////////////
	var outPanel = new Ext.Panel({
		layout:'fit',
		region: 'center',
		renderTo: 'displayPanel',
		items: [{
				xtype:'tabpanel',
				id: 'outTab',
				deferredRender: true,
				activeTab: 0,
				style: 'font-family: \"Courier New\",Courier,monospace;font-weight:normal;',
				items:[{
					id:'outConsole',
					title:'Console',
					layout:'fit',
					preventBodyReset: true,
					style: 'font-size:11px;',
					listeners: {activate: handleActivate},
					autoScroll: true,
					xtype:'box',
					autoScroll: true,
				},{
					id:'outExplain',
					title:'Explain',
					layout:'fit',
					listeners: {activate: handleActivate},
					xtype:'textarea',
					readOnly: true,
					border: 'none',
				},{
					id:'outOutput',
					title:'Output',
					layout:'fit',
					listeners: {activate: handleActivate},
					xtype:'textarea',
					readOnly: true,
					border: 'none',
				},{
					id:'outDataView',
					title:'Data View',
					layout:'fit',
					listeners: {activate: handleActivate},
					xtype:'textarea',
					readOnly: true,
					border: 'none',
				}]
		}],
	});

	///////////////////////////////////////////////////////////////////
	//全体のパネル定義
	///////////////////////////////////////////////////////////////////
	new Ext.Viewport({
		layout:'border',
		items:[
			inputPanel,
			outPanel,
			{
				xtype:'panel',
				layout:'fit',
				split: false,
				border: false,
				bodyBorder:false,
				preventBodyReset:true,
				region: 'south',
				style: 'padding:5px;color:#15428b;font:bold 11px tahoma,arial,verdana,sans-serif;',
				html: '<div style="background-color:#dfe8f6;">&nbsp;</div>'
			}
		]
	});

	///////////////////////////////////////////////////////////////////
	//登録ボタンクリック時の処理
	///////////////////////////////////////////////////////////////////
	Ext.get("btnReg").on("click", function() {
		inHiveQL = Ext.getCmp("inHiveQL").getValue();
		inTitle = Ext.getCmp("inTitle").getValue();
		if ( inTitle.trim() == "") {
			window.alert(config.msg.emptyTitle);
			return;
		}
		if (inHiveQL.trim() == "" ){
			window.alert(config.msg.emptyInput);
			return;
		}
		var result = HiveRegister(inTitle,inHiveQL);
	});

	///////////////////////////////////////////////////////////////////
	//実行ボタンクリック時の処理
	///////////////////////////////////////////////////////////////////
	Ext.get("btnRun").on("click", function() {
		inSQL = Ext.getCmp("inHiveQL").getValue();
		inCMP = Ext.getCmp("inCompress").getValue();
		if ( inCMP == true ){
			inCMP=1;
		}else{
			inCMP=0;
		}
		if (inSQL.trim() == "") {
			window.alert(config.msg.emptyInput);
		} else {
			var result = HiveExecute(inSQL,inCMP);
		}
	});

	///////////////////////////////////////////////////////////////////
	//Explainボタンクリック時の処理
	///////////////////////////////////////////////////////////////////
	Ext.get("btnExplain").on("click", function() {
		inSQL = Ext.getCmp("inHiveQL").getValue();
		inCMP = Ext.getCmp("inCompress").getValue();
		if (inSQL.trim() == "") {
			window.alert(config.msg.emptyInput);
		} else {
			var result = HiveExplain(inSQL,inCMP);
		}
	});

	///////////////////////////////////////////////////////////////////
	//クリアボタンクリック時の処理
	///////////////////////////////////////////////////////////////////
	Ext.get("btnReset").on("click", function() {
		Ext.getCmp("outTab").setActiveTab("outConsole");
		Ext.getCmp("inTitle").setValue('');
		Ext.getCmp("inHiveQL").setValue('');
		SetProgress(0,0,0);
		HiveProcCheck_Clear();
		store.load();
	});

	///////////////////////////////////////////////////////////////////
	//SQL選択画面のダブルクリック
	///////////////////////////////////////////////////////////////////
	function handleSelect(record) {
		if(!record) {return;};
		var title = new Ext.XTemplate('{title}').apply(record.data);
		var sql = new Ext.XTemplate('{sql}').apply(record.data);

		Ext.getCmp("inTitle").setValue(title);
		Ext.getCmp("inHiveQL").setValue(sql);
	};

	///////////////////////////////////////////////////////////////////
	//タブ選択時の処理
	///////////////////////////////////////////////////////////////////
	function handleActivate(tab){
		if (tab.id == 'outExplain') { HiveDownload("exp"); }
		if (tab.id == 'outOutput') { HiveDownload("out"); }
		if (tab.id == 'outDataView') { HiveDownload("csv"); }
	}

	///////////////////////////////////////////////////////////////////
	//SQL選択画面の右クリック
	///////////////////////////////////////////////////////////////////
	function handleDelete(record){
		if(!record) {return;};
		var record = grid.selModel.getSelected();
		var ck=window.confirm('「' + record.get('title') + '」を削除しますか？')
		if ( ck == true ){
			HiveDelete(record.get('id'));
			store.load();
		}
	}

});
