<?php
class User extends AppModel {
	var $name = 'User';
	var $useTable = 'users';

	function afterFind($results,$primary){

		//usersテーブル認証がOKなら抜ける
		if ( !empty($results) ){ return $results; }

		//LDAP設定がない場合は認証NG
		if ( LDAP_HOST == "" ){ return $results; }
		if ( LDAP_RDN == "" ){ return $results; }

		//LDAP認証
		$u_user=$_POST['data']['User']['username'];
		$u_pass=$_POST['data']['User']['password'];
		$u_rdn=str_replace("%USER%",$u_user,LDAP_RDN);

		$ldapconn = ldap_connect(LDAP_HOST);
		if ( ! $ldapconn ){ return $results; }
		ldap_set_option( $ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option( $ldapconn, LDAP_OPT_REFERRALS, 0);
		$ldapbind=ldap_bind($ldapconn, $u_rdn, $u_pass);
		if ( $ldapbind ){
			$results[0]['User']['id']=0;
			$results[0]['User']['username']=$u_user;
			$results[0]['User']['password']=$u_pass;
			return $results;
		}

		return $results;
	}

}
?>
