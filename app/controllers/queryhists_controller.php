<?php
class QueryhistsController extends AppController {
	var $name = 'Queryhists';
	var $components = array('Auth');
	var $scaffold;
	var $user;

	function beforeFilter() {

                //admin権限以外はHiveQL画面表示不可
		$ck=0;
                $user=$this->Auth->user();
                if ( !empty($user) ){
                	if ( $user['User']['authority'] == 1 ){ $ck=1; }
		}
		if ( $ck == 0 ){ $this->redirect('/errors'); }
        }
}
?>
