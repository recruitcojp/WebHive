Ext.onReady(function(){

	Ext.QuickTips.init();

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

	var fp = new Ext.FormPanel({
		renderTo: 'fi-form',
		fileUpload: true,
		width: 500,
		frame: true,
		title: 'ファイルアップロード',
		autoHeight: true,
		bodyStyle: 'padding: 10px 10px 0 10px;',
		labelWidth: 100,
		defaults: {
			anchor: '95%',
			allowBlank: false,
			name:'outdir'
		},
		items: [{
			xtype: 'fileuploadfield',
			id: 'form-file',
			emptyText: 'ファイルを選択',
			fieldLabel: 'ファイル',
			name: 'filenm',
			buttonText: '参照'
		},{
			xtype: 'textfield',
			fieldLabel: 'HDFS上の出力先'
		}],
		buttons: [{
			text: '送信',
			handler: function(){
				if(fp.getForm().isValid()){
						fp.getForm().submit({
							url: '/WebHive/uploads/fileupload',
							waitMsg: 'ファイルを送信中...',
							success: function(fp, o){
								if (o.result.success){
									msg('Success', 'ファイルアップロードが正常終了しました。');
								}else{
									Ext.Msg.alert('エラー',o.result.msg).setIcon(Ext.Msg.ERROR);
								}
							},
							failure:function(fp, o){
								Ext.Msg.alert('エラー',o.result.msg).setIcon(Ext.Msg.ERROR);
							}
						});
				}
			}
		},{
			text: 'リセット',
			handler: function(){
				fp.getForm().reset();
			}
		}]
	});

});
