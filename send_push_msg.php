<?php
 $AUTH_KEY_PATH = "file://".__DIR__.DIRECTORY_SEPARATOR."your p8 file";
 $AUTH_KEY_ID = 'your AUTH_KEY_ID';
 $TEAM_ID = 'your TEAM_ID';
 $BUNDLE_ID = 'your BUNDLE_ID';
 $token;
 $server_key;
 $title;
 $body;
 $image;

 function decodeMsg($message) {
  $messageInJson = json_decode($message, true);
  if ($messageInJson["title"]==null) {		
   return false;
  }
  if ($messageInJson["body"]==null) {		
   return false;
  }
  if ($messageInJson["image"]==null) {		
   return false;
  }
  $title = $messageInJson["title"];
  $body = $messageInJson["body"];
  $image = $messageInJson["image"];
 }

 function sendToIosServer() {
  $payload = [
   'aps'  => [
    //'alert' => [
    // 'title' => 'test',
    // "subtitle"=> "show iOS 10 support!",
    // "body" => "love you",
    // 
    //],
    'alert'   => [
     'title'  => $title,
     'body'   => $body,
    ],
    'sound'           => 'default',
    'mutable-content' => 1,
    'badge'           => 1,
   ],
   'data'  => [
    'image' => $image,
   ]
  ];
  
  //// Create The JWT
  $header = base64_encode(json_encode([
   'alg' => 'ES256',
   'kid' => AUTH_KEY_ID
  ]));
  $claims = base64_encode(json_encode([
   'iss' => TEAM_ID,
   'iat' => time()
  ]));
  $pkey = openssl_pkey_get_private(AUTH_KEY_PATH);
  openssl_sign("$header.$claims", $signature, $pkey, 'sha256');
  $signed = base64_encode($signature);
  $signedHeaderData = "$header.$claims.$signed";
  
  //Setup curl
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
  curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($payload));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
   'apns-topic: ' . BUNDLE_ID,
   'authorization: bearer ' . $signedHeaderData,
   'apns-push-type: alert'
  ]);
  
  $url = "https://api.development.push.apple.com/3/device/$token";
  //Making the call
  curl_setopt($ch, CURLOPT_URL, "{$url}");
  $response = curl_exec($ch);
  if ($response) {
   echo $response."<br>";
  }
  // DEAL WITH IT ('it' being errors)
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  echo $code."<br>";
  curl_close($ch);
 }

 function sendToAndroidServer() {
  echo $token;
  $url = 'https://fcm.googleapis.com/fcm/send';
  $fields['registration_tokens'] = array($token);
  $fields['to'] = '/topics/my-app';
  $title = 'test';
     
  $content = array(
   'title'	  => $title,
 		'body' 	  => $body,
 		'vibrate' => 1,
   'sound'   => 1,
   'image'   => $image
  );
 	$fields = array(
   'to'		    => $token,
 		'notification'	=> $content
 	);
  $headers = array(
   'Content-Type:application/json',
   'Authorization:key='.$server_key
  );
     
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
  $result = curl_exec($ch);
  echo $response."<br>";
  curl_close($ch);
  var_dump($result);exit;
 }

 function sendMsg($device,$deviceInfoArr,$bundleIDInfoStr,$bundleIDInfoArr,$message) {
  $AUTH_KEY_PATH = $bundleIDInfoArr['key_path'];
  $AUTH_KEY_ID = $bundleIDInfoArr['key_id'];
  $TEAM_ID = $bundleIDInfoArr['teamID'];
  $BUNDLE_ID = $bundleIDInfoArr['bundleID'];
  $token = $deviceInfoArr['token'];
  $server_key = $bundleIDInfoStr;
  decodeMsg($message);
  if ($device=='android') {
   sendToAndroidServer();
  } else if($device=='ios') {
   sendToIosServer();
  }
 }
?>