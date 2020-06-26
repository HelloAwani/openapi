<?php

namespace Service\Http\Controllers\Dev\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;
use Pushok\AuthProvider;
use Pushok\Client;
use Pushok\Notification;
use Pushok\Payload;
use Pushok\Payload\Alert;

class Push extends \Service\Http\Controllers\_Heart
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->api_version =  "v1";
	}
	
	public function apn(){
		

		//$this->validate_request();

			echo "
				<html>
					<form id = 'fn' name = 'fn' method = 'POST'>
					<label>Device Token</label> <br>
					<input type = 'text' name = 'DeviceToken' value = '".@$_REQUEST['DeviceToken']."' > <br>
					<label>Title</label> <br>
					<input type = 'text' name = 'Title' value = '".@$_REQUEST['Title']."' /> <br>
					<label>Body</label> <br>
					<textarea = 'text' name = 'Body' style='margin: 0px; width: 384px; height: 135px;'>".@$_REQUEST['Body']."</textarea> <br>
					<input type = 'submit'>
					</form>
				</html>
			
			";

		if($_SERVER['REQUEST_METHOD']=="POST"){

			$rules = [		
			];
			//standar validator
			$this->validator($rules);
			$this->render();

			$options = [
				'key_id' => env('APN_KEY'), // The Key ID obtained from Apple developer account
				'team_id' => env('APN_TEAM'), // The Team ID obtained from Apple developer account
				'app_bundle_id' => env('APN_BUNDLE'), // The bundle ID for app obtained from Apple developer account
				'private_key_path' => storage_path(env('APN_P8')), // Path to private key
				'private_key_secret' => null // Private key secret
			];
			

			$authProvider = AuthProvider\Token::create($options);

			$alert = Alert::create()->setTitle($_REQUEST["Title"]);
			$alert = $alert->setBody($_REQUEST["Body"]);

			$payload = Payload::create()->setAlert($alert);

			//set notification sound to default
			$payload->setSound('default');

			//add custom value to your notification, needs to be customized
			$payload->setCustomValue('IntegerValue', 1);
			$payload->setCustomValue('StringValue', "String");
			$payload->setCustomValue('FloatValue', 1.5);
			$payload->setCustomValue('BooleanValue', false);
			$payload->setCustomValue('ArrayValue', [
				"Col1" => 1,
				"Col2" => "Col2"
			]);

			$deviceTokens = [$_REQUEST["DeviceToken"]];

			$notifications = [];
			foreach ($deviceTokens as $deviceToken) {
				$notifications[] = new Notification($payload,$deviceToken);
			}

			$client = new Client($authProvider, $production = false);
			$client->addNotifications($notifications);



			$responses = $client->push(); // returns an array of ApnsResponseInterface (one Response per Notification)

			print_r($responses);




			echo "OK";

		}
	}



}

