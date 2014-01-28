<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo APP_TITLE ?></title>
<link rel="stylesheet" type="text/css" href="/WebHive/ext/resources/css/ext-all.css" />
<link rel="stylesheet" type="text/css" href="/WebHive/css/progress-bar.css" />
<script type="text/javascript" src="/WebHive/ext/bootstrap.js"></script>
<script type="text/javascript" src="/WebHive/js/config.js"></script>
<style TYPE="text/css">
<!--
	.details {background-image: url(/WebHive/img/details.gif) !important;}
	.preview-bottom {background-image: url(/WebHive/img/preview-bottom.gif) !important;}
	.preview-right {background-image: url(/WebHive/img/preview-right.gif) !important;}
	.preview-hide {background-image: url(/WebHive/img/preview-hide.gif) !important;}
	.query-button {background-image: url(/WebHive/img/application_go.png) !important;}
-->
</style>
<script type="text/javascript">
//<![CDATA[
<?php echo "var userid='".$user['User']['username']."';\n"; ?>
//]]>
</script>
<script type="text/javascript" >
<?php 
$u_auth=$user['User']['authority'];
$auth_flg=Configure::read("USER_AUTH_${u_auth}");
echo $this->element('ui',array("auth_flg"=>$auth_flg)); 
echo $this->element('core',array("auth_flg"=>$auth_flg)); 
?>
</script>
</head>
<body>

<?php echo $this->element('banner'); ?>

<div id="header" style="padding:3px;font-weight:normal;font-size:12px;color:#15428b;" >
<table width='100%'><tr>
<td align="left"><?php echo APP_TITLE ?><?php echo $app_title_msg; ?></td>
<td align="right">
<?php echo $user['User']['username']."さん"; ?>
&nbsp; <a href="/WebHive/users/logout">LOGOUT</a>
&nbsp; <a href="/WebHive/entity">Hive構成情報表示</a>
<?php if ( TITLE_URL1 != "" ){ echo "&nbsp;" . TITLE_URL1; } ?>
<?php if ( TITLE_URL2 != "" ){ echo "&nbsp;" . TITLE_URL2; } ?>
&nbsp; <a href="/WebHive/help" onclick="window.open('/WebHive/help','_blank','width=900,height=500,scrollbars=yes'); return false;">変更履歴</a>
&nbsp;
</td>
</tr></table>
</div>
<div id="displayPanel" ></div>  
</body>
</html>
