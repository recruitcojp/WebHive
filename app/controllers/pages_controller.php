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
		$user=$this->user;

		//usersテーブル検索
		$this->loadModel('Users');
		$username=$user['User']['username'];
		$users=$this->Users->find('all', array('conditions' => "username='$username'"));
		if ( count($users) > 0 ){
			$user['User']['authority']=$users[0]['Users']['authority'];
		}

		//権限情報が未設定の場合(LDAP認証やWebHiveリポジトリでauthorityが未設定の場合)
		if ( empty($user['User']['authority']) ){
			$user['User']['authority']=LDAP_AUTH;
		}
		$this->set('user', $user);
	}
}
?>
