Ext.BLANK_IMAGE_URL = 'ext/resources/images/default/s.gif';

<?php echo "[$user_auth][$upload_flg]\n"; ?>

Ext.onReady(function() {

	///////////////////////////////////////////////////////////////////
	//カスタムイベント
	///////////////////////////////////////////////////////////////////
	MyEvent = Ext.extend(Ext.util.Observable, {
		constructor: function() {
			this.addEvents('HiveReloadEvent');
			this.on('HiveReloadEvent', function() {
				storeSQL.load();
			});
		}
	});

	///////////////////////////////////////////////////////////////////
	//Grid
	///////////////////////////////////////////////////////////////////
	//データストアの設定(登録済みクエリ表示)
	var storeSQL = new Ext.data.JsonStore({
		url:'/WebHive/apis/select',
		totalProperty:'total',
		root:'row',
		fields:[
			{name:'id'},
			{name:'username'},
			{name:'title'},
			{name:'sql'}
		],
		autoLoad:true
	});
	storeSQL.load();

	//データストアの設定(利用可能データベース)
	var storeDatabase = new Ext.data.JsonStore({
		url:'/WebHive/apis/database',
		totalProperty:'total',
		root:'row',
		fields:[
			{name:'id'},
			{name:'caption'}
		]
	});
	storeDatabase.load({ params:{ u:userid } });

	//SelModelの設定
	var sm = new Ext.grid.RowSelectionModel({
		singleSelect:true
	});

	//カラムモデルの設定
	var column = new Ext.grid.ColumnModel([ {
			id:'username',
			header:'USERNAME',
			dataIndex:'username',
			width:100
		}, {
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
		store:storeSQL,
		listeners:{
			rowdblclick:function(grid, row, e){
				var record = grid.getStore().getAt(row);
				handleSelect(record);
			} 
<?php if ( $user_auth==1 or $user_auth==2){
			echo ",rowcontextmenu:function(grid, row, e){\n";
			echo "	grid.getSelectionModel().selectRow(row);\n";
			echo "	e.stopEvent();\n";
			echo "	var record = grid.getStore().getAt(row);\n";
			echo "	handleDelete(record);\n";
			echo "}\n";
}?>
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
			items: grid,
			split: true,
			width: 500
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
				id: 'inDatabase',
				xtype: 'combo',
				fieldLabel: 'Database',
				store: storeDatabase,
				valueField: "id",
				displayField: "caption",
				value:'default',
				editable: false,
				triggerAction: 'all',
				mode: 'local'
			},{
				id: 'inTitle',
				xtype: 'textfield',
				fieldLabel: 'Title',
				<?php if ( $user_auth==3 ){ echo "readOnly: true,\n"; } ?>
				width: '100%'
			},{
				id: 'inHiveQL',
				xtype: 'textarea',
				fieldLabel: 'HiveQL',
				<?php if ( $user_auth==3 ){ echo "readOnly: true,\n"; } ?>
				width: '100%',
				height: 80
			},
			new Ext.form.CheckboxGroup({
				xtype:'fieldset',
				fieldLabel: 'Output',
				defaultType: 'checkbox',
				layout: 'column',
				style:'margin:1px;',
				style: 'font-family: \"Courier New\",Courier,monospace;font-weight:normal;font-size:12px;',
				defaults: {columnWidth: '.32', border: false },
				items: [
					{id:'inCompress', name:'inCompress', boxLabel:'zip圧縮', checked: true },
					{id:'inColumn', name:'inColumn', boxLabel:'カラム名の有無', checked: true }
				]
			})
			,{
				id: 'inStageProgress',
				xtype: 'progress',
				fieldLabel: 'Stage(%)',
				width: '90%'
			},{
				id: 'inMapProgress',
				xtype: 'progress',
				fieldLabel: 'Map(%)',
				width: '90%'
			},{
				id: 'inRedProgress',
				xtype: 'progress',
				fieldLabel: 'Reduce(%)',
				width: '90%'
			}]
		}],
		buttons: [
		{ id:'btnRun', text: config.ui.btnRun },
		{ id:'btnReset', text: config.ui.btnReset },
		{ id:'btnExplain', text: config.ui.btnExplain }
		<?php if ( $user_auth==1 or $user_auth==2){ echo ",{ id:'btnReg', text: config.ui.btnReg }\n"; } ?>
		<?php if ( $user_auth==1 ){ echo ",{ id:'btnSql', text: config.ui.btnSql }\n"; } ?>
		<?php if ( $user_auth==1 and $upload_flg==1 ){ echo ",{ id:'btnUpload', text: config.ui.btnUpload }\n"; } ?>
		]
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
					autoScroll: true
				},{
					id:'outExplain',
					title:'Explain',
					layout:'fit',
					listeners: {activate: handleActivate},
					xtype:'textarea',
					readOnly: true,
					border: 'none'
				},{
					id:'outOutput',
					title:'Output',
					layout:'fit',
					listeners: {activate: handleActivate},
					xtype:'textarea',
					readOnly: true,
					border: 'none'
				},{
					id:'outDataView',
					title:'Data View',
					layout:'fit',
					listeners: {activate: handleActivate},
					xtype:'textarea',
					readOnly: true,
					border: 'none'
				}]
		}]
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

<?php if ( $upload_flg==1 ){ 
	echo "///////////////////////////////////////////////////////////////////\n";
	echo "//ファイルアップロード\n";
	echo "///////////////////////////////////////////////////////////////////\n";
	echo "Ext.get(\"btnUpload\").on(\"click\", function() {\n";
	echo "	window.open(\"/WebHive/uploads\", \"\", \"width=550,height=250\");\n";
	echo "});\n";
} ?>

<?php if ( $user_auth==1 ){ 
	echo "///////////////////////////////////////////////////////////////////\n";
	echo "//SQL管理画面ボタンクリック時の処理\n";
	echo "///////////////////////////////////////////////////////////////////\n";
	echo "Ext.get(\"btnSql\").on(\"click\", function() {\n";
	echo "	window.open(\"/WebHive/hiveqls\", \"\", \"width=900,height=500,scrollbars=yes\");\n";
	echo "});\n";
} ?>

<?php if ( $user_auth==1 or $user_auth==2){
	echo "///////////////////////////////////////////////////////////////////\n";
	echo "//登録ボタンクリック時の処理\n";
	echo "///////////////////////////////////////////////////////////////////\n";
	echo "Ext.get(\"btnReg\").on(\"click\", function() {\n";
	echo "	inHiveQL = Ext.getCmp(\"inHiveQL\").getValue();\n";
	echo "	inTitle = Ext.getCmp(\"inTitle\").getValue();\n";
	echo "	if ( inTitle.trim() == \"\") {\n";
	echo "		Ext.Msg.alert(config.msg.checkInput, config.msg.emptyTitle);\n";
	echo "		return;\n";
	echo "	}\n";
	echo "	if (inHiveQL.trim() == \"\" ){\n";
	echo "		Ext.Msg.alert(config.msg.checkInput, config.msg.emptyQuery);\n";
	echo "		return;\n";
	echo "	}\n";
	echo "	var result = HiveRegister(inTitle,inHiveQL);\n";
	echo "});\n";
} ?>

	///////////////////////////////////////////////////////////////////
	//実行ボタンクリック時の処理
	///////////////////////////////////////////////////////////////////
	Ext.get("btnRun").on("click", function() {
		inDB = Ext.getCmp("inDatabase").getRawValue();
		inSQL = Ext.getCmp("inHiveQL").getValue();
		inCMP = Ext.getCmp("inCompress").getValue();
		inCOL = Ext.getCmp("inColumn").getValue();
		if ( inCMP == true ){ inCMP="Z"; }else{ inCMP="N"; }
		if ( inCOL == true ){ inCOL="C"; }else{ inCOL="N"; }
		if (inSQL.trim() == "") {
			Ext.Msg.alert(config.msg.checkInput, config.msg.emptyQuery);
		} else {
			var result = HiveExecute(inDB,inSQL,inCMP,inCOL);
		}
	});

	///////////////////////////////////////////////////////////////////
	//Explainボタンクリック時の処理
	///////////////////////////////////////////////////////////////////
	Ext.get("btnExplain").on("click", function() {
		inDB = Ext.getCmp("inDatabase").getRawValue();
		inSQL = Ext.getCmp("inHiveQL").getValue();
		inCMP = Ext.getCmp("inCompress").getValue();
		inCOL = Ext.getCmp("inColumn").getValue();
		if ( inCMP == true ){ inCMP="Z"; }else{ inCMP="N"; }
		if ( inCOL == true ){ inCOL="C"; }else{ inCOL="N"; }
		if (inSQL.trim() == "") {
			Ext.Msg.alert(config.msg.checkInput, config.msg.emptyQuery);
		} else {
			var result = HiveExplain(inDB,inSQL,inCMP,inCOL);
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
		storeSQL.load();
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

<?php if ( $user_auth==1 or $user_auth==2){
	echo "///////////////////////////////////////////////////////////////////\n";
	echo "//SQL選択画面の右クリック\n";
	echo "///////////////////////////////////////////////////////////////////\n";
	echo "function handleDelete(record){\n";
	echo "	if(!record) {return;};\n";
	echo "	var record = grid.selModel.getSelected();\n";
	echo "	Ext.Msg.confirm(config.msg.checkDelete, '「' + record.get('title') + '」を削除しますか？' ,function(btn){\n";
	echo "		if(btn == 'yes'){ \n";
	echo "			HiveDelete(record.get('id'));\n";
	echo "			storeSQL.load();\n";
	echo "		}\n";
	echo "	});\n";
	echo "}\n";
} ?>

});
