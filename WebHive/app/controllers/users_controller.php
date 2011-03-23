<?php
class UsersController extends AppController {
	var $name = 'Users';
	var $components = array('Auth');
	var $scaffold;

	//ログイン処理
	function login() {
	}

	//ログアウト処理
	function logout() {
		$this->redirect($this->Auth->logout());
	}

	function beforeRender() {
	}

	function beforeFilter() {
		$this->Auth->allow('index');
		$this->Auth->allow('add');
		$this->Auth->allow('edit');
		$this->Auth->allow('list');
		$this->Auth->allow('delete');
		$this->Auth->allow('view');

		//表示権限がない場合はリダイレクト
		$ret=UsersController::page_check();
		if ( $ret == 0 ){
			$this->redirect('/');
		}
	}

	//ユーザ管理画面の表示許可判定
	function page_check() {
		//常に許可
		if ( $this->params['action'] == "login" ){ return 1; }
		if ( $this->params['action'] == "logout" ){ return 1; }

		//未ログイン状態でも許可
		if ( USER_ADMIN == 0 ){ return 1; }

		//admin権限だけ許可
		$user=$this->Auth->user();
		if ( empty($user) ){ return 0; }
		if ( $user['User']['authority'] == 1 ){ return 1; }
		return 0;
	}
}
?>
