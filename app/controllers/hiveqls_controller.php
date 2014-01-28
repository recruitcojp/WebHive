<?php
class HiveqlsController extends AppController {
	var $name = 'Hiveqls';
	var $components = array('Auth','Common');
	var $scaffold;
	var $user;

	function beforeFilter() {
		$this->set('app_title_msg', CommonComponent::GetSubTitle());

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
