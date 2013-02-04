<?php
class PagesController extends AppController {
	var $name = 'Pages';
	var $components = array('Auth');
	var $user;

	function index() {
	}

	function display() {
	}

	function beforeFilter() {
	}

	function beforeRender() {
		$this->layout='base';
		$this->user=$this->Auth->user();

		//usersテーブル検索
		$this->loadModel('Users');
		$username=$this->user['User']['username'];
		$users=$this->Users->find('all', array('conditions' => "username='$username'"));
		if ( count($users) > 0 ){
			$this->user['User']['authority']=$users[0]['Users']['authority'];
		}

		//権限情報が未設定の場合(LDAP認証やWebHiveリポジトリでauthorityが未設定の場合)
		if ( empty($this->user['User']['authority']) ){
			$this->user['User']['authority']=LDAP_AUTH;

			//初回ログイン時はDB登録
			$reg=array();
			$reg['Users']['username']=$this->user['User']['username'];
			$reg['Users']['authority']=$this->user['User']['authority'];
			$this->Users->create();
			$this->Users->save($reg, array('username','authority'));
		}

		$this->set('user', $this->user);
	}
}
?>
