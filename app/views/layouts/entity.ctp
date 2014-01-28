<html>
<head>
<title>hive configuration</title>
<link href="/WebHive/css/main.css" rel="stylesheet">
</head>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" >
<a name='page_top'></a>

<?php echo $this->element('banner'); ?>

<table width="100%" height="100%" cellspacing="0" cellpadding="0">
	<tr height="5" bgcolor="#dfe9f6" class="noprint">
		<td colspan="2">
			<table width="100%">
				<tr>
<td align="left">
<?php echo APP_TITLE ?><?php echo $app_title_msg; ?>
</td>
					<td align="right">
<?php echo $user['User']['username']."さん"; ?>&nbsp;&nbsp;
｜<a href='/WebHive/users/logout'>LOGOUT</a>
｜<a href='/WebHive/'>WebHive</a> 
｜<a href="/WebHive/help" onclick="window.open('/WebHive/help','_blank','width=900,height=500,scrollbars=yes'); return false;">変更履歴</a>｜
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr class="noprint">
		<td bgcolor="#efefef" colspan="1" height="8" style="background-image: url(/WebHive/img/shadow_gray.gif); background-repeat: repeat-x; border-right: #aaaaaa 1px solid;">
			<img src="/WebHive/img/transparent_line.gif" width="200" height="2" border="0"><br>
		</td>
		<td bgcolor="#ffffff" colspan="1" height="8" style="background-image: url(/WebHive/img/shadow.gif); background-repeat: repeat-x;">
		</td>
	</tr>

	
	<tr>
	<td valign="top" style="padding: 5px; border-right: #aaaaaa 1px solid;">
	<script type="text/javascript">
	<!--
	function applyGraphPreviewFilterChange(objForm) {
		strURL = '?action=preview';
		strURL = strURL + '&database_id=' + objForm.database_id.value;
		strURL = strURL + '&table_id=' + objForm.table_id.value;
		strURL = strURL + '&filter=' + objForm.filter.value;
		document.location = strURL;
	}
	-->
	</script>
	<table width='100%' style='background-color: #f5f5f5; border: 1px solid #bbbbbb;' align='center' cellpadding='3'>
	<tr bgcolor="E5E5E5" class="noprint">
		<form name="form_graph_view" method="post">
		<td class="noprint">
			<table width="100%" cellpadding="0" cellspacing="0">

				<tr class="noprint">
					<td nowrap style='white-space: nowrap;' width="40">
						&nbsp;<strong>Database:</strong>&nbsp;
					</td>
					<td width="1">
						<select name="database_id" onChange="applyGraphPreviewFilterChange(document.form_graph_view)">
<?php print($para['database']); ?>
						</select>
					</td>
					<td nowrap style='white-space: nowrap;' width="40">
						&nbsp;<strong>Table:</strong>&nbsp;
					</td>
					<td width="1">
						<select name="table_id" onChange="applyGraphPreviewFilterChange(document.form_graph_view)">
<?php print($para['table']); ?>
						</select>
					</td>

					<td nowrap style='white-space: nowrap;' width="50">
						&nbsp;&nbsp;<strong>&nbsp;Filter:</strong>&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="40" value="<?php print($para['filter']); ?>">
					</td>

					<td style='white-space:nowrap;' nowrap>
						&nbsp;<input type="image" src="/WebHive/img/button_go.gif" alt="Go" border="0" align="absmiddle">
						<input type="image" src="/WebHive/img/button_clear.gif" name="clear" alt="Clear" border="0" align="absmiddle">
					</td>
					<td nowrap style='white-space: nowrap;' width="10">
						<a href="/WebHive/entity/download?database_id=<?php print($para['database_id']); ?>">DOWNLOAD</a>
					</td>
				</tr>
			</table>
		</td>
		</form>
	</tr>

	</table><table width='100%' style='background-color: #f5f5f5; border: 1px solid #bbbbbb;' align='center' cellpadding='3'>
		</table><br>
<table width='100%' style='background-color: #f5f5f5; border: 1px solid #bbbbbb;' align='center' cellpadding='1'>
	<tr bgcolor='#17385b' class='noprint'>
		<td colspan='2'>
			<table width='100%' cellspacing='0' cellpadding='3' border='0'>
				<tr>
					<td align='left' class='textHeaderDark' width='15%'>
<?php print($para['previous']); ?>
											</td>
					<td align='center' class='textHeaderDark' width='70%'>
<?php print($para['rows']); ?>
											</td>
					<td align='right' class='textHeaderDark' width='15%'>
<?php print($para['next']); ?>
											</td>
				</tr>
			</table>
		</td>
	</tr>

	<tr style='background-color: #F2F2F2;'>
		<td align='center' width='100%'>
				<table align='center' cellpadding='0' width='100%'>
					<tr>
						<td align='center'>
<?php print($para['data']); ?>
						</td>
					</tr>
				</table>
			</td>
			<td align='center' width='50%'>
		</td>
	</tr>

	<tr bgcolor='#17385b' class='noprint'>
		<td colspan='2'>
			<table width='100%' cellspacing='0' cellpadding='3' border='0'>
				<tr>
					<td align='left' class='textHeaderDark' width='15%'>
<?php print($para['previous']); ?>
											</td>
					<td align='center' class='textHeaderDark' width='70%'>
<?php print($para['rows']); ?>
											</td>
					<td align='right' class='textHeaderDark' width='15%'>
<?php print($para['next']); ?>
											</td>
				</tr>
			</table>
		</td>
	</tr>
	</table>			<br>
		</td>
	</tr>
</table>

</body>
</html>

