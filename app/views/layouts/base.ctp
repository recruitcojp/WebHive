<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo APP_TITLE ?></title>
<link rel="stylesheet" type="text/css" href="/WebHive/ext/resources/css/ext-all.css" />
<script type="text/javascript" src="/WebHive/ext/adapter/ext/ext-base.js"></script>
<script type="text/javascript" src="/WebHive/ext/ext-all.js"></script>
<script type="text/javascript" src="/WebHive/js/prototype.js"></script>
<script type="text/javascript" src="/WebHive/js/config.js"></script>
<script type="text/javascript" src="/WebHive/js/core.js"></script>
<?php
if ( $user['User']['authority'] == 1 ){
	echo "<script type=\"text/javascript\" src=\"/WebHive/js/ui_admin.js\"></script>\n";
}else if ( $user['User']['authority'] == 2 ){
	echo "<script type=\"text/javascript\" src=\"/WebHive/js/ui_select.js\"></script>\n";
}else{
	echo "<script type=\"text/javascript\" src=\"/WebHive/js/ui_guest.js\"></script>\n";
}
?>
</head>
<body>
<script type="text/javascript">
//<![CDATA[
<?php echo "var userid='".$user['User']['username']."';\n"; ?>
//]]>
</script>
<div id="header" style="padding:3px;font-weight:normal;font-size:12px;color:#15428b;" align="right">
<?php echo $user['User']['username']."さん"; ?>
&nbsp; <a href="/WebHive/users/logout">LOGOUT</a> &nbsp;
</div>
<div id="displayPanel"></div>  
</div>
</body>
</html>
