<?php
class User extends AppModel {
	var $name = 'User';
	var $useTable = 'users';

	function afterFind($results,$primary){

		if ( empty($_POST['data']) ){
			$u_user="";
			$u_pass="";
		}else{
			$u_user=$_POST['data']['User']['username'];
			$u_pass=$_POST['data']['User']['password'];
		}

		if ( $u_user != "" and $u_pass == "" ){
			$this->LoginAuditLogWrite($u_user,"NG","login error");
			$results=array();
			return $results; 
		}

		//usersテーブル認証がOKなら抜ける
		if ( !empty($results) ){
			$this->LoginAuditLogWrite($u_user,"OK","db user login");
			return $results; 
		}

		//LDAP設定がない場合は認証NG
		if ( LDAP_HOST == "" or LDAP_RDN == "" ){
			$this->LoginAuditLogWrite($u_user,"NG","db user login error");
			return $results; 
		}

		//LDAP接続
		$ldapconn = ldap_connect(LDAP_HOST);
		if ( ! $ldapconn ){
			$this->LoginAuditLogWrite($u_user,"NG","ldap connect error");
			return $results; 
		}
		ldap_set_option( $ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option( $ldapconn, LDAP_OPT_REFERRALS, 0);

		//LDAP ディレクトリにバインド
		$u_rdn=str_replace("%USER%",$u_user,LDAP_RDN);
		$ldapbind=ldap_bind($ldapconn, $u_rdn, $u_pass);
		if ( $ldapbind == TRUE ){
			$this->LoginAuditLogWrite($u_user,"OK","ldap user login");
			$results[0]['User']['id']=0;
			$results[0]['User']['username']=$u_user;
			$results[0]['User']['password']=$u_pass;
			return $results;
		}

		//認証NG
		$this->LoginAuditLogWrite($u_user,"NG","ldap user login error");
		return $results;
	}

	function LoginAuditLogWrite($user_id,$result,$detail){
		if ( DIR_AUDIT_LOG == "" ){ return 1; }
		if ( ! is_dir(DIR_AUDIT_LOG) ){ return 1; }

		//監査ログ
		$today = getdate();
		$audit_log_file=sprintf("%s/webhive_login.log",DIR_AUDIT_LOG);
		$ymdhms=sprintf("%04d/%02d/%02d %02d:%02d:%02d",
			$today['year'],$today['mon'],$today['mday'],
			$today['hours'],$today['minutes'],$today['seconds']);

		//監査ログ出力
		if ( !($fp=fopen($audit_log_file,"a")) ){ return 1; }
		fputs($fp,"$ymdhms\t$user_id\t$result\t$detail\n");
		fclose($fp);
		return 0;
	}
}
?>
