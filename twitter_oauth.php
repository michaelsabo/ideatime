<?php
require("twitteroauth/twitteroauth/twitteroauth.php");  
session_start();

if(!empty($_GET['oauth_verifier']) && !empty($_SESSION['oauth_token']) && !empty($_SESSION['oauth_token_secret'])){  
    // TwitterOAuth instance, with two new parameters we got in twitter_login.php  
	$twitteroauth = new TwitterOAuth('0sd51MbJuom5csE6xeYfw', 'suL2lwFTggjBMWRSev1uZDIutYy7vhhHo44DIOYs', $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);  
	// Let's request the access token  
	$access_token = $twitteroauth->getAccessToken($_GET['oauth_verifier']); 
	// Save it in a session var 
	$_SESSION['access_token'] = $access_token; 
	// Let's get the user's info 
	$user_info = $twitteroauth->get('account/verify_credentials'); 
	// Print user's info  
	//print_r($user_info);

	/* write to the db */
	mysql_connect('localhost', 'root', 'root');  
	mysql_select_db('fitness');
	
	if(!empty($_SESSION['username'])){  
	    // User is logged in, redirect  
	    header('Location: index.php');  
	} 

	if(isset($user_info->error)){  
    // Something's wrong, go back to square 1  
    	header('Location: error.php'); 
	} else { 
    // Let's find the user by its ID  
    $query = mysql_query("SELECT * FROM users WHERE oauth_provider = 'twitter' AND oauth_uid = ". $user_info->id);  
    $result = mysql_fetch_array($query);  
    $isAuthor = null;
  
    // If not, let's add it to the database  
    if(empty($result)){  
        $query = mysql_query("INSERT INTO users (oauth_provider, oauth_uid, username, oauth_token, oauth_secret) VALUES ('twitter', {$user_info->id}, '{$user_info->screen_name}', '{$access_token['oauth_token']}', '{$access_token['oauth_token_secret']}')");  
        $query = mysql_query("SELECT * FROM users WHERE id = " . mysql_insert_id());  
        $result = mysql_fetch_array($query);  
    } else {  
        // Update the tokens  
        $query = mysql_query("UPDATE users SET oauth_token = '{$access_token['oauth_token']}', oauth_secret = '{$access_token['oauth_token_secret']}' WHERE oauth_provider = 'twitter' AND oauth_uid = {$user_info->id}");  
		/* check user permission */
      	$query = mysql_query("SELECT id FROM users_whitelist WHERE username ='". $result['username'] . "'");        
        $val = mysql_fetch_array($query);          
        if(!empty($val)){
        	$isAuthor = $val['id'];
        }   
    }  

    $_SESSION['id'] = $result['id']; 
    $_SESSION['author'] = $isAuthor; 
    $_SESSION['username'] = $result['username']; 
    $_SESSION['oauth_uid'] = $result['oauth_uid']; 
    $_SESSION['oauth_provider'] = $result['oauth_provider']; 
    $_SESSION['oauth_token'] = $result['oauth_token']; 
    $_SESSION['oauth_secret'] = $result['oauth_secret'];   
 
    header('Location: index.php');  
} 



} else {  
    // Something's missing, go back to square 1  
    header('Location: index.php');  
} 
?>