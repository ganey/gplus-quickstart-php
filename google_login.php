<?php
/*
	@Author Michael Gane - http://ganey.co.uk
	Date: December 18th 2013 

	The information in this document is mostly from https://developers.google.com and is licensed under
	the Creative Commons Attribution 3.0 License, and the majority of code can be found within the 
	Google Developer Platform at https://developers.google.com/+/

	This is a modification of the example found at https://developers.google.com/+/quickstart/php

	This gets some basic user information within PHP itself, rather than running more javascript
	orientated like in the example.

	This 
	- Setup preoject or use existing at https://cloud.google.com/console#/project
	- Add Google+ API in APIs & Auth -> APIs
	- In APIs & Auth, set javascript origins to domainname to work from
	- setup const CLIENT_ID, CLIENT_SECRET, APPLICATION_NAME & DEVELOPER_KEY
	where CLIENT_ID and CLIENT_SECRET come from the OAuth section within Credentials under APIs & Auth
	- DEVELOPER_KEY is not required for many features, but may be required.
	It can be found under Public API access as the API key for "Key for browser applications" 
	within Credentials under APIs & auth.

	These have been set in google_config.php

	the autoload is the easy way to load required libraries and class files and comes from

	git clone https://github.com/googleplus/gplus-quickstart-php.git

	or downlaod with:

	wget https://github.com/googleplus/gplus-quickstart-php/archive/master.zip
	unzip gplus-quickstart-php-master.zip

*/
require_once 'google_config.php';
require_once __DIR__.'/gplus-quickstart-php/vendor/autoload.php'; //path to the autoload created with (php composer.phar install) from step 2 of the original documentation
/*
	instead of autoload, the following files can be used:
	
	require_once 'src/Google_Client.php';
	require_once 'src/contrib/Google_PlusService.php';
	
	as these are all the current code requires.
*/
session_start();

if (isset($_GET['state']) && $_SESSION['state'] != $_GET['state']) {
  header('HTTP/ 401 Invalid state parameter');
  exit;
}

$client = new Google_Client();
$client->setApplicationName(APPLICATION_NAME);
$client->setClientId(CLIENT_ID);
$client->setClientSecret(CLIENT_SECRET);
$client->setRedirectUri($_SERVER['SERVER_NAME']."/index.php"); //change $_SERVER['SERVER_NAME'] to your url (e.g. http://example.com/phpdev/google/test) or keep for server root.
$client->setDeveloperKey(DEVELOPER_KEY);

$state = md5(rand());
$_SESSION['state'] = $state;

$plus = new Google_PlusService($client);

if (isset($_GET['code'])) {
  $client->authenticate();
  // Get your access and refresh tokens, which are both contained in the
  // following response, which is in a JSON structure:
  $jsonTokens = $client->getAccessToken();
  $_SESSION['token'] = $jsonTokens;
  // Store the tokens or otherwise handle the behavior now that you have
  // successfully connected the user and exchanged the code for tokens. You
  // will likely redirect to a different location in your app at thsi point.
}
if (isset($_SESSION['token'])) {
	$client->setAccessToken($_SESSION['token']);
}

if (isset($_REQUEST['logout'])) {
	unset($_SESSION['token']);
	unset($_SESSION['google_data']); //Google session data unset
	$client->revokeToken();
}

if ($client->getAccessToken()) 
{
	$token = json_decode($client->getAccessToken());
	$attributes = $client->verifyIdToken($token->id_token, CLIENT_ID)
            ->getAttributes();
    $gplus_id = $attributes["payload"]["sub"]; //this is a unique google ID and can be used for your authentication
    //basic user info
    $me = $plus->people->get('me');
    //output for testing purposes only to prove the information that is held within php
    echo "<pre>";
    print_r($attributes);
    
    print_r($me);
    echo "</pre>";
	//header("location: home.php"); //redirect to some page
	$_SESSION['token'] = $client->getAccessToken();

	echo "<a href='?logout'>Sign Out</a>";
} else {
	/* not logged in 
	- Redirect URI is set in https://cloud.google.com/console#/project
	- 		-> Your Project
	-		-> Credentials
	-		-> Client ID for web applications
	-		-> Redirect URIs

	below the scope variables can be as follows (space separated):
	https://developers.google.com/+/api/oauth#scopes

	profile
	- basic login scope

	https://www.googleapis.com/auth/plus.login
	- basic login with social features (google reccommended scope)

	email
	- the users google account email

	https://www.googleapis.com/auth/plus.profile.emails.read
	- the public email addresses held within the google profile for the user

	https://www.googleapis.com/auth/plus.me
	- not reccommended for login, less useful for non google+ users

	openid
	- requests authenticated users id, tells auth server the request is OpenID Connect
	- returns the user profile in OIDC-compliant format 

	*/
	echo "<a href=\"https://accounts.google.com/o/oauth2/auth?scope=".
      "profile email&".
      "state=".$state."&".
      "redirect_uri=http://".$_SERVER['SERVER_NAME']."/index.php&". 
      "response_type=code&".
      "client_id=".CLIENT_ID."&".
      "access_type=offline\">Sign In With Google</a>";
}
