Ext.onReady(function(){

	Ext.QuickTips.init();

	//メッセージダイアログ
	var msg = function(title, msg){
		Ext.Msg.show({
			title: title,
			msg: msg,
			minWidth: 200,
			modal: true,
			icon: Ext.Msg.INFO,
			buttons: Ext.Msg.OK
		});
	};

        //データストアの設定(ユーザ一覧)
        var storeUsers = Ext.create('Ext.data.Store', {
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
                        {name: 'username'},
                        {name: 'authority'},
                        {name: 'hive_database'}
                ],
                autoLoad:false
        });
        storeUsers.load();

        //データストアの設定(ロール)
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
                autoLoad:false
        });
        storeRoles.load();

	//フォーム表示
	var fp = new Ext.FormPanel({
		renderTo: 'fi-form',
		width: 450,
		frame: true,
		title: 'ユーザ権限管理画面',
		autoHeight: true,
		bodyStyle: 'padding: 10px 10px 0 10px;',
		labelWidth: 100,
		items: [{
			id: 'username',
			xtype: 'combo',
			store: storeUsers,
		 	emptyText: 'ユーザを選択してください',
			fieldLabel: 'ユーザ名',
			editable: false,
			valueField: "id",
			anchor: '95%',
			displayField: "username",
			listeners:{
				change: function(f, nv, ov) {
					userid=storeUsers.getAt(nv).raw.userid;
					username=storeUsers.getAt(nv).raw.username;
					userauth=storeUsers.getAt(nv).raw.authority;
					hive_database=storeUsers.getAt(nv).raw.hive_database;
					Ext.getCmp("userrole").setValue(userauth);
					Ext.getCmp("hive_database").setValue(hive_database);
				}
			}
		},{
			id: 'userrole',
			xtype: 'combo',
			store: storeRoles,
		 	emptyText: 'ロールを選択してください',
			fieldLabel: 'ロール権限',
			editable: false,
			valueField: "id",
			anchor: '95%',
			displayField: "rolename"
		},{
			id: 'hive_database',
			xtype: 'textfield',
			allowBlank: true,
			anchor: '90%',
			fieldLabel: '許可データベース'
		},{
			id: 'inputTips',
			xtype: 'button',
			iconCls:'help-button',
			width: 20,
			listeners:{
				click:  function(button,event){
					title='許可データベース設定';
					msg='ユーザ単位に許可するデータベースを設定できます。<br>';
					msg=msg + '<br>【設定例】<br>';
					msg=msg + '<table>';
					msg=msg + '<tr><td width="40%">db01</td><td width="60%">db01を許可</tr>';
					msg=msg + '<tr><td>db01|db02</td><td>db01とdb02を許可</tr>';
					msg=msg + '<tr><td>hoge(.*)</td><td>hogeから始まるDBを許可</tr>';
					msg=msg + '<tr><td>!hoge(.*)</td><td>hoge以外から始まるDBを許可</td></tr>';
					msg=msg + '</table>';

					Ext.Msg.show({
					title: title,
					msg: msg,
					minWidth: 200,
					modal: true,
					buttons: Ext.Msg.OK
					});
				}
			}
		}],
		buttons: [{
			text: '更新',
			handler: function(){
				id=Ext.getCmp("username").getValue();
				userid=storeUsers.getAt(id).raw.userid;
				userauth=Ext.getCmp("userrole").getValue();
				hive_database=Ext.getCmp("hive_database").getValue();
				storeUsers.getAt(id).raw.useerauth=userauth;
				storeUsers.getAt(id).raw.hive_database=hive_database;

				//入力チェック
				if ( userid == undefined || userauth == undefined ){
					Ext.Msg.alert('入力確認', '未入力項目があります').setIcon(Ext.Msg.WARNING);
					return;
				}

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
						if ( res.result == "ok" ){
							msg('Success', 'ユーザ情報を更新しました');
						}else{
							Ext.Msg.alert('処理確認', 'ユーザ情報の更新が失敗しました').setIcon(Ext.Msg.ERROR);
						}
					},
					failure:function(result,opt){
						Ext.Msg.alert('処理確認', 'ユーザ情報の更新が失敗しました').setIcon(Ext.Msg.ERROR);
					}
				});
			}
		},{
			text: '閉じる',
			handler: function(){
				window.close();
			}
		}]
	});

});
