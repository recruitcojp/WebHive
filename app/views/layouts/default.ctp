<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo APP_TITLE ?></title>
<?php
echo $this->Html->meta('icon');
echo $this->Html->css('cake.generic');
echo $scripts_for_layout;
?>
</head>
<body>
<div id="container">
	<div id="header">
	<h1><?php echo APP_TITLE ?><?php if( !empty($app_title_msg) ){ echo $app_title_msg; } ?></h1>
	</div>
	<div id="content">
		<?php echo $this->Session->flash(); ?>
		<?php echo $content_for_layout; ?>
	</div>
	<div id="footer">
		<p>Copyright 2011 RECRUIT CO.,LTD.</p>
	</div>
</div>
<?php echo $this->element('sql_dump'); ?>
</body>
</html>
