<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo APP_TITLE ?></title>
<link rel="stylesheet" type="text/css" href="/WebHive/ext/resources/css/ext-all.css" />
<script type="text/javascript" src="/WebHive/ext/adapter/ext/ext-base.js"></script>
<script type="text/javascript" src="/WebHive/ext/ext-all.js"></script>
<script type="text/javascript" src="/WebHive/js/config.js"></script>
<script type="text/javascript" src="/WebHive/js/core.js"></script>
<script type="text/javascript" >
<?php echo $this->element('ui',array("user_auth"=>$user['User']['authority'], "upload_flg"=>FILE_UPLOAD_FLG)); ?>
</script>
<style TYPE="text/css">
<!--
	.details {background-image: url(/WebHive/img/details.gif) !important;}
	.preview-bottom {background-image: url(/WebHive/img/preview-bottom.gif) !important;}
	.preview-right {background-image: url(/WebHive/img/preview-right.gif) !important;}
	.preview-hide {background-image: url(/WebHive/img/preview-hide.gif) !important;}
-->
</style>
</head>
<body>
<script type="text/javascript">
//<![CDATA[
<?php echo "var userid='".$user['User']['username']."';\n"; ?>
//]]>
</script>
<div id="header" style="padding:3px;font-weight:normal;font-size:12px;color:#15428b;" align="right">
<?php echo $user['User']['username']."さん"; ?>
&nbsp; <a href="/WebHive/users/logout">LOGOUT</a>
&nbsp; <a href="/WebHive/help" onclick="window.open('/WebHive/help','_blank','width=900,height=500,scrollbars=yes'); return false;">変更履歴</a>
&nbsp;
</div>
<div id="displayPanel" ></div>  
</body>
</html>
