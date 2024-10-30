<?php
/*********************************************************************************************************
Description:

	Connection class to send and retrieve data from and to mango
	
*********************************************************************************************************/

	class mango_ssl_call {
		
		var $URL = 'https://www.mangossl.com/MasterSite/rest.php';
		
		// general posting method 
		
		function post($url,$style,$value)
		{
			$response = wp_remote_post( $url, array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array('Content-Type'=>$style),
				'body'        => $value,
				'cookies'     => array()
			));
			
			if (is_wp_error($response)) 
			{
				$error_message = $response->get_error_message();
				return false;
			} 
			
			return $response['body'];
		}
		
		// login request to get a valid mango token
		
		function login($domain, $login, $pwd)
		{
			$request = array();
			
			$request['action'] = 'login';
			$request['DOMAIN'] = $domain;
			$request['LOGIN'] = $login;
			$request['PWD'] = $pwd;
			$request['nonce'] = wp_create_nonce($domain.'-'.$login);
			
			$value = json_encode($request);
			
			$style = 'application/json';
			
			if (($result = $this->post($this->URL,$style,$value))===false)
				return null;
			else
			{
				$reply = json_decode($result);
				
				if ($reply==null || !isset($reply->nonce))
					return null;
				
				if ( !wp_verify_nonce( $reply->nonce, $domain.'-'.$login)) 
				{
					error_log( 'Security check' ); 
					return null;
				} 
				else 
				{
					if ($reply->success!=1)
						return null;
					else if ($reply->result->EXIST==-1)
						return null;
					else
						return $reply->result;
				}
			}
		}
		
		// request function with authentication details, action to perform and object to update/create
		
		function send($action, $type, $obj)
		{
			$request = array();
			
			$request['authenticate'] = get_option('mangossl_token');
			$request['action'] = $action;
			$request['object'] = $type;
			$request['nonce'] = wp_create_nonce($action.'-'.$type.'-'.$obj->id);
			$request['content'] = $obj;
			
			$value = json_encode($request);
			
			$style = 'application/json';
			
			error_log('REQUEST: '.$value);
			
			if (($respons = $this->post($this->URL,$style,$value))===false)
				return null;
			else
			{
				error_log('RESPONS: '.$respons);
				$reply = json_decode($respons);
				
				if ($reply==null || !isset($reply->nonce))
					return null;
				
				if ( !wp_verify_nonce( $reply->nonce, $action.'-'.$type.'-'.$obj->id)) 
				{
					error_log( 'Security check' ); 
					return null;
				} 
				else 
				{
					if ($reply->success!=1)
						return null;
					else if ($reply->result->EXIST==-1)
						return null;
					else
						return $reply->result;
				}
			}
		}
	}
?>
