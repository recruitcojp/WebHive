<?php
class UsersController extends AppController {
	var $name = 'Users';
	var $components = array('Auth','Common');
	var $scaffold;

	//ユーザ管理画面
	function auth() { 
		$this->layout = "user_auth";

		//ユーザ情報
		$this->loadModel('Users');
		$user=$this->Auth->user();
		$username=$user['User']['username'];
		$users=$this->Users->find('all', array('conditions' => "username='$username'"));
		$userauth=$users[0]['Users']['authority'];

		//操作権限チェック
		if ( $userauth == "" ){
			$this->redirect('/errors');
		}
		$wk=Configure::read("USER_AUTH_${userauth}");
		if ( $wk['user_mgr'] != 1 ){
			$this->redirect('/errors');
		}

		//ユーザ一覧
		$users=$this->Users->find('all', array('conditions' => "authority!=1"));
	}

	//個別ユーザ管理画面
	function management() { 
		$this->layout = "user_mgr";

		//ユーザ情報取得
		$this->loadModel('Users');
		$user=$this->Auth->user();
		$username=$user['User']['username'];
		$users=$this->Users->find('all', array('conditions' => "username='$username'"));
		$userauth=$users[0]['Users']['authority'];

		//操作権限チェック
		if ( $userauth == "" ){
			$this->redirect('/errors');
		}
		$wk=Configure::read("USER_AUTH_${userauth}");
		if ( $wk['user_mgr'] != 1 ){
			$this->redirect('/errors');
		}
	}

	//ログイン処理
	function login() { }

	//ログアウト処理
	function logout() {
		$this->redirect($this->Auth->logout());
	}

	function beforeRender() {
		$this->set('app_title_msg', CommonComponent::GetSubTitle());
	}

	function beforeFilter() {
		$this->Auth->allow('index');
		$this->Auth->allow('add');
		$this->Auth->allow('edit');
		$this->Auth->allow('list');
		$this->Auth->allow('delete');
		$this->Auth->allow('view');

		//表示権限がない場合はリダイレクト
		$ret=UsersController::_page_check();
		if ( $ret == 0 ){
			$this->redirect('/');
		}
	}

	//ユーザ管理画面の表示許可判定
	protected function _page_check() {
		//常に許可
		if ( $this->params['action'] == "login" ){ return 1; }
		if ( $this->params['action'] == "logout" ){ return 1; }

		//ユーザ権限管理画面
		if ( $this->params['action'] == "auth" ){ return 1; }
		if ( $this->params['action'] == "management" ){ return 1; }

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
