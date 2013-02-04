var config={};
var brlang = getBrowserLanguage();

if (brlang == "ja") {
	config = {
		ui: {
			inputEmptyText : 'ここに HiveQL を入力し[実行]ボタンを押下してください',
			titleSelect: 'HiveQL選択',
			titleInput: 'HiveQL入力',
			btnCreDB: 'DB作成',
			btnDataUpload: 'Data Upload',
			btnUpload: 'File Upload',
			btnSql: 'クエリ管理画面',
			btnUpd: 'クエリ情報更新',
			btnReg: 'クエリ登録',
			btnRun: 'クエリ実行',
			btnExplain: 'クエリチェック',
			btnReset: 'リセット'
		},
		msg: {
			checkProcess: '処理確認',
			checkInput: '入力確認',
			checkDelete: '削除確認',
			checkCreDB: 'DB作成確認',
			UploadEnd: 'データのアップロードが正常終了しました。',
			emptyQuery: 'HiveQLが未入力です',
			emptyTitle: 'タイトルが未入力です',
			emptyDBname: 'データベース名が未入力です',
			noSupportDML: '入力されたSQLはサポートしていません'
		}
	}
} else {
	config = {
		ui: {
			inputEmptyText : 'Please input HiveQL here and push the Run button.',
			titleSelect: 'HiveQL Select',
			titleInput: 'HiveQL Input',
			btnCreDB: 'Create Database',
			btnDataUpload: 'Data Upload',
			btnUpload: 'File Upload',
			btnSql: 'HiveQL Management',
			btnUpd: 'HiveQL Download',
			btnReg: 'HiveQL Register',
			btnExplain: 'HiveQL Explain',
			btnRun: 'HiveQL Run',
			btnReset: 'Reset'
		},
		msg: {
			checkProcess: 'confirm',
			checkInput: 'confirm',
			checkDelete: 'confirm',
			checkCreDB: 'confirm',
			UploadEnd: 'Data Upload Normal End',
			emptyQuery: 'HiveQL is a uninput.',
			emptyTitle: 'Title is a uninput.',
			emptyDBname: 'Database Name is a uninput.',
			noSupportDML: 'HiveQL is not supported.'
		}
	}
}

function getBrowserLanguage() {
	var lang = "ja";

	// IE
	if (document.all) {
		lang = navigator.browserLanguage;
	// N4
	} else if (document.layers) {
		lang = navigator.language ;
	//N6,Moz用
	} else {
		lang = navigator.language.substr(0,2);
	}
	return lang;
}

