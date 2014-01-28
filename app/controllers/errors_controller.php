<?php
class ErrorsController extends AppController {
	var $name = 'Errors';
	var $components = array('Common');

	function index() {
		$this->set('app_title_msg', CommonComponent::GetSubTitle());
	}
}
?>
