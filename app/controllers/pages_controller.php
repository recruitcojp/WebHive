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
		if ( empty($user['User']['authority']) ){
			$user['User']['authority']=LDAP_AUTH;
		}
		$this->set('user', $user);
	}
}
?>
