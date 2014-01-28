var sv_rolename="";

///////////////////////////////////////
//追加モジュール
///////////////////////////////////////
Ext.require([
	'Ext.data.*',
	'Ext.tip.QuickTipManager',
	'Ext.window.MessageBox'
]);


///////////////////////////////////////
//データストア設定(ロール)
///////////////////////////////////////
var storeRoles = Ext.create('Ext.data.Store', {
	proxy: {
		type: 'ajax',
		actionMethods : 'POST',
		url:'/WebHive/apis/roles',
		totalProperty:'total',
		reader: {
			type: 'json',
			root:'row'
		}
	},
	fields: [
		{name: 'id'},
		{name: 'rolename'}
	],
	autoLoad:true
});

///////////////////////////////////////
//データストア設定（ユーザ）
///////////////////////////////////////
var storeUser = Ext.create('Ext.data.Store', {
	proxy: {
		type: 'ajax',
		actionMethods : 'POST',
		url:'/WebHive/apis/users',
		totalProperty:'total',
		reader: {
			type: 'json',
			root:'row'
		}
	},
	fields: [
		{name: 'id'},
		{name: 'userid'},
		{name: 'username'},
		{name: 'rolename'},
		{name: 'authority'},
		{name: 'hive_database'}
	],
	autoLoad:true
});


///////////////////////////////////////
//画面壁画
///////////////////////////////////////
Ext.onReady(function(){
	Ext.tip.QuickTipManager.init();

	//コンテナ
	//var main = Ext.create('Ext.container.Container', {
	var main = Ext.create('Ext.container.Viewport', {
	padding: '10 10 10 10',
	renderTo: document.body,
	layout: {
		type: 'vbox',
		align: 'stretch'
	},
	items: [{
		itemId: 'form',
		xtype: 'writerform',
		height: 150,
		margins: '0 0 10 0'
	}, {
		itemId: 'grid',
		xtype: 'writergrid',
		title: 'ユーザ一覧',
		flex: 1,
		width: '100%',
		height: '95%',
		store: storeUser,
		listeners: {
			selectionchange: function(selModel, selected) {
				main.child('#form').setActiveRecord(selected[0] || null);
			}
		}
	}]
	});
});


///////////////////////////////////////
//ユーザ権限変更
///////////////////////////////////////
Ext.define('Writer.Form', {
	extend: 'Ext.form.Panel',
	alias: 'widget.writerform',

	requires: ['Ext.form.field.Text'],

	initComponent: function(){

	Ext.apply(this, {
		activeRecord: null,
		iconCls: 'icon-user',
		frame: true,
		title: 'ユーザ権限管理',
		defaultType: 'textfield',
		bodyPadding: 5,
		fieldDefaults: {
			anchor: '100%',
			labelAlign: 'right'
		},
		items: [{
			fieldLabel: 'USER ID',
			name: 'userid',
			readOnly: true,
			hidden:true,
			allowBlank: false
		}, {
			fieldLabel: 'ユーザ名',
			name: 'username',
			readOnly: true,
			allowBlank: false
		}, {
			fieldLabel: 'ロール権限',
			name: 'authority',
			xtype: 'combo',
			allowBlank: false,
			store: storeRoles,
			editable: false,
			displayField: "rolename",
			valueField: "id",
			listeners: {
				change: function(selModel, selected) {
					sv_rolename=selModel.rawValue;
				}
			}
		}, {
			fieldLabel: '許可データベース',
			name: 'hive_database',
			allowBlank: true
		}],
		dockedItems: [{
			xtype: 'toolbar',
			dock: 'bottom',
			ui: 'footer',
			items: ['->', {
				iconCls: 'icon-save',
				itemId: 'save',
				text: 'Save',
				disabled: true,
				scope: this,
				handler: this.onSave
			}, {
				iconCls: 'icon-reset',
				text: 'Reset',
				scope: this,
				handler: this.onReset
		},{
			id: 'inputTips',
			xtype: 'button',
			iconCls:'help-button',
			width: 20,
			listeners:{
				click:  function(button,event){
					title='ユーザ権限設定';
					msg='';
					msg=msg + '【許可データベース】<br>';
					msg=msg + 'ユーザ毎に利用できるデータベースを設定できます。<br>';
					msg=msg + '<br>【設定例】<br>';
					msg=msg + '<table>';
					msg=msg + '<tr><td width="25%">空欄</td><td width="75%">全てのデータベースを利用可</tr>';
					msg=msg + '<tr><td width="25%">db01</td><td width="75%">db01のみ利用可</tr>';
					msg=msg + '<tr><td width="25%">db01|db02</td><td width="75%">db01とdb02のみ利用可</tr>';
					msg=msg + '<tr><td width="25%">db(.*)</td><td width="75%">dbから始まるデータベースのみ利用可</tr>';
					msg=msg + '<tr><td width="25%">!db(.*)</td><td width="75%">db以外から始まるデータベースのみ利用可</td></tr>';
					msg=msg + '<tr><td width="25%">invalid</td><td width="75%">全てのデータベースを利用不可(存在しない名前を指定)</td></tr>';
					msg=msg + '</table>';

					Ext.Msg.show({
					title: title,
					msg: msg,
					width: 400,
					modal: true,
					buttons: Ext.Msg.OK
					});
				}
			}
			}]
		}]
	});

	this.callParent();

	},

	setActiveRecord: function(record){
		this.activeRecord = record;
		if (record) {
			this.down('#save').enable();
			this.getForm().loadRecord(record);
		} else {
			this.down('#save').disable();
			this.getForm().reset();
		}
	},

	onSave: function(){
		var active = this.activeRecord,
		form = this.getForm();
		if (!active) { return; }
		if (form.isValid()) {
			//グリッド更新
			active.data.rolename=sv_rolename;
			form.updateRecord(active);

			userid=active.data.userid;
			userauth=active.data.authority;
			hive_database=active.data.hive_database;

			//更新リクエスト
			Ext.Ajax.request({
				url:'/WebHive/apis/usermodify',
				method:'POST',
				params:{
					userid:userid,
					userauth:userauth,
					hive_database:hive_database
				},
				success:function(result,opt){
					var res = Ext.decode(result.responseText);
					if ( res.result != "ok" ){
						Ext.Msg.alert('処理確認', 'ユーザ情報の更新が失敗しました').setIcon(Ext.Msg.ERROR);
					}
				},
				failure:function(result,opt){
					Ext.Msg.alert('処理確認', 'ユーザ情報の更新が失敗しました').setIcon(Ext.Msg.ERROR);
				}
			});
		}
	},

	onReset: function(){
		this.setActiveRecord(null);
		this.getForm().reset();
	}
});


///////////////////////////////////////
//ユーザ一覧画面
///////////////////////////////////////
Ext.define('Writer.Grid', {
	extend: 'Ext.grid.Panel',
	alias: 'widget.writergrid',

	requires: [
	'Ext.grid.plugin.CellEditing',
	'Ext.form.field.Text',
	'Ext.toolbar.TextItem'
	],

	initComponent: function(){
	Ext.apply(this, {
		iconCls: 'icon-grid',
		frame: true,
		height: '100%',
		columns: [{
			text: 'USER ID',
			width: 20,
			hidden:true,
			sortable: true,
			dataIndex: 'userid'
		}, {
			header: 'ユーザ名',
			width: 150,
			readOnly: true,
			sortable: true,
			dataIndex: 'username',
			field: { type: 'textfield' }
		}, {
			header: 'ロール権限',
			width: 200,
			sortable: true,
			readOnly: true,
			dataIndex: 'rolename',
			field: { type: 'textfield' }
		}, {
			header: '許可データベース',
			width: 200,
			sortable: true,
			readOnly: true,
			dataIndex: 'hive_database',
			field: { type: 'textfield' }
		}]
	});
	this.callParent();
	}
});

///////////////////////////////////////
//バリデーション
///////////////////////////////////////
Ext.define('Writer.Person', {
	extend: 'Ext.data.Model',
	fields: [{
		name: 'userid',
		type: 'int',
		useNull: true
	}, 'username', 'authority', 'rolename', 'hive_database'],
	validations: [{
		type: 'length',
		field: 'username',
		min: 1
	}, {
		type: 'length',
		field: 'authority',
		min: 1
	}, {
		type: 'length',
		field: 'hive_database',
		min: 1
	}]
});


