<?php

namespace Service\Http\Controllers\OpenTransaction\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

use Pushok\AuthProvider;
use Pushok\Client;
use Pushok\Notification;
use Pushok\Payload;
use Pushok\Payload\Alert;


class Transaction extends \Service\Http\Controllers\_Heart
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->api_version =  "v1";
		$this->enforce_product  = "OpenTransaction";
		ini_set('display_errors', '1');
		ini_set('display_startup_errors', '1');
		error_reporting(E_ALL);
	}

	public function submit(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;

		$this->_token_detail->BranchID  = $this->coalesce($this->request["_BranchID"], $this->_token_detail->BranchID);
		$this->_token_detail->MainID  = $this->coalesce($this->request["_MainID"], $this->_token_detail->MainID);


		$rules = [
			'Date' => 'required'		
		];
		//standar validator
		$this->validator($rules);
		$ext_trans_id = 0;
		$now = $this->now()->full_time;
		
		$dbtoken = [];
		switch ($this->MappingMeta->SubProduct) {
			case 'RES':{

					$ex = $this->query('SELECT * 
						from "ExtCustomer" ec 
						join "Handler" h on h."ExtCustomerID" = ec."ExtCustomerID"
						where ec."ExtCustomerID" = :id and ec."ExtensionID" = :eid
						and h."HandlerID" = :hid
						',[
						"id"=>@$this->request["ExtCustomerID"],
			 			"eid"=> $this->ACMeta->ExtName,
						"hid"=>@$this->request["HandlerID"],
					]);

					if(count($ex)==0){
						$this->add_error("CH", "CH", "Customer/Handler ID is Invalid");	
					}
					$this->render();

					$trans = [
						"Date" => @$this->request["Date"],
						"Name" => @$this->request["Name"],
						"ExtCustomerID" => @$this->request["ExtCustomerID"],
						"Address" => @$this->request["Address"],
						"Phone" => @$this->request["Phone"],
						"Email" => @$this->request["Email"],
						"GrandTotal" => @$this->request["GrandTotal"],
						"HandlerID" => @$this->request["HandlerID"],
						"CreatedDate" => @$this->now()->full_time,
						"ProductID" => @$this->MappingMeta->SubProduct,
						"BranchID" => @$this->_token_detail->BranchID,
						"MainID" => @$this->_token_detail->MainID
					];

					$this->db  = $this->MappingMeta->SubProduct;

					$modifier  = $this->query('
					SELECT m."MenuID", mi."ModifierItemID"
											From "MenuModifier" mm
											join "Menu" m on m."MenuID" = mm."MenuID"
											left join "ModifierGroup" mg on mg."ModifierGroupID" = mm."ModifierGroupID"
											left join "ModifierItem" mi on mg."ModifierGroupID" = mi."ModifierGroupID"
					
											AND (mi."Archived" is null or mi."Archived" = \'N\')
											AND (mg."Archived" is null or mg."Archived" = \'N\')
											where m."BranchID" = :BranchID and  m."RestaurantID" = :MainID  
											order by m."MenuCode"
						',
						[
							"BranchID"=> $this->_token_detail->BranchID,
							"MainID"=> $this->_token_detail->MainID
						]
					);
					$menu  = $this->query('
					SELECT m."MenuID" from "Menu" m
											where m."BranchID" = :BranchID and  m."RestaurantID" = :MainID  
											AND (m."Archived" is null or m."Archived" = \'N\')
											order by m."MenuCode"
						',
						[
							"BranchID"=> $this->_token_detail->BranchID,
							"MainID"=> $this->_token_detail->MainID
						]
					);
					$menu_id = $this->extract_column($menu, "MenuID", []);
					$mod_id = $this->extract_column($modifier, "ModifierItemID", []);

					$this->db  = $this->_token_detail->ProductID;

					$this->start();
					$ext_trans_id = $this->upsert("ExtTransaction", $trans);
					foreach ($this->request["Items"] as $item) {
						$dtl = [
							"ExtTransactionID" => @$ext_trans_id,
							"ItemID" => @$item["ItemID"],
							"ItemName" => @$item["ItemName"],
							"Price" => @$item["Price"],
							"Qty" => @$item["Qty"]
						];
						if(!in_array(@$item["ItemID"], $menu_id)){
							$this->add_error("Item", "Item", "Item ID {$item["ItemID"]} is Invalid");	
						}
						$this->render();
						$ext_dtl_id = $this->upsert("ExtTransactionDetail", $dtl);


						if(is_array(@$item["Modifiers"])){

							foreach ($item["Modifiers"] as $mod) {
								$mdtl = [
									"ExtTransactionDetailID" => @$ext_dtl_id,
									"ModifierID" => @$mod["ModifierID"],
									"ModifierName" => @$mod["ModifierName"],
									"Price" => @$mod["Price"],
								];

								if(!in_array(@$mod["ModifierID"], $mod_id)){
									$this->add_error("Modifier", "Modifier", "Modifier ID {$mod["ModifierID"]} is Invalid");	
								}
								$this->render();
								$this->upsert("ExtTransactionModifier", $mdtl);

							}
						}
					}

					$this->end();
					$this->db = "RES";
					$dbtoken = $this->query('SELECT "DeviceToken","Plattform"  FROM "Token" where "BranchID" = :BranchID and "RestaurantID" = :MainID and "DeviceToken" is not null and "Disabled" Is Null ',
						[
							"BranchID"=> $this->_token_detail->BranchID,
							"MainID"=> $this->_token_detail->MainID
						]
					);
				break;
			}
			default:
				# code...
				break;
		}
		$this->db  = $this->_token_detail->ProductID;

		$this->response->push_response = [];
		foreach($dbtoken as $t){
			
			if($t->Plattform=="iOS"){
				$options = [
					'key_id' => env('APN_KEY'), // The Key ID obtained from Apple developer account
					'team_id' => env('APN_TEAM'), // The Team ID obtained from Apple developer account
					'app_bundle_id' => env('APN_BUNDLE'), // The bundle ID for app obtained from Apple developer account
					'private_key_path' => storage_path(env('APN_P8')), // Path to private key
					'private_key_secret' => null // Private key secret
				];
				
		
				$authProvider = AuthProvider\Token::create($options);
		
				$alert = Alert::create()->setTitle("New Order");
				$alert = $alert->setBody("Grabfood new order");
		
				$payload = Payload::create()->setAlert($alert);
		
				//set notification sound to default
				$payload->setSound('default');
		
				//add custom value to your notification, needs to be customized
				$payload->setCustomValue('ExtTransactionID', $ext_trans_id);
		
				$deviceTokens = [$t->DeviceToken];
				$notifications = [];
				foreach ($deviceTokens as $deviceToken) {
					$notifications[] = new Notification($payload,$deviceToken);
				}
		
				$client = new Client($authProvider, $production = false);
				$client->addNotifications($notifications);
		
				$pResponses = $client->push(); 
				foreach($pResponses as $pr){
					$this->response->push_responsep[] = $pr;
				}
		
			}

			if($t->Plattform =='Android'){
				$recipients = array(
					$t->DeviceToken
				);
				
				$res = fcm()
					->to($recipients) // $recipients must an array
					->priority('high')
					->timeToLive(0)
					// ->data([
					// 	'title' => 'ExtTransactionID',
					// 	'body' => $ext_trans_id,
					// ])
					->data([
						'title' => 'Grabfood New Order',
						'body' => 'You\'ve got a new order from Grab food at '.$now,
					])
					->notification([
						'title' => 'Grabfood New Order',
						'body' => 'You\'ve got a new order from Grab food at '.$now,
					])
					->send();
				
			}
			
			$this->response->push_response[] = $res;
		}
		
		$this->response->ExtTransactionID = $ext_trans_id;

		$this->reset_db();
		$this->render(true);
	}

	public function test_android(){
		
		
		$recipients = [
			'fK7yKynXRt6h2CVoKdBLlX:APA91bH9MCtpRHUfCVRwFHtrz8UMV-NOVWUvSzQIeDl-QfPg5PsaBV1CUHccIBQq7Q-r8J2HWwRHwe-F4oAXkZLO5WjLfTI6tncXjVHrfeg7OZhy0Sj6mSqIy1-ZjXXMaU5k3r_zKcA_',
			'fsNWrzkqSWeE_HKtt0P3qN:APA91bFyIDLr1o3DKrBXYboH2kPGoPspGpiWt2R5G35LgrLA8D5DjC9UR6YZGaLRxxvusOpYrZivSEMsy4Zq88iYKUBWleMm8aDXxBhUukfiM9yDyxj4YdrrllpBVW5xB2c0FYLRxsPh'
		];
		$data = $this->request['Data'];
		$notif = $this->request['Notification'];
		$data = json_decode(json_encode($data),true);
		$notif = json_decode(json_encode($notif),true);
		
		$res = fcm()
		->to($recipients) // $recipients must an array
		->priority('high')
		->timeToLive(0)
		->data($data)->notification($notif)
		// ->data([
		// 	'title' => 'INI CERITANYA EXT TRANSACTION',
		// 	'body' => 'INI CERITANYA ID NYA',
		// ])
		// ->notification([
		// 	'title' => 'WOI NOTIF WOI',
		// 	'body' => 'WOI NOTIF WOI',
		// ])
		->send();
		print_r(json_encode($res));die();
	}

	public function fetch(){

		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;



		$rules = [
			'Date' => 'required',
			'Status' => 'required',
		];
		//standar validator
		$this->validator($rules);

		$where = "";
		switch (@$this->request["Status"]) {
			case 'Closed':
				$where.=' and "FinishedDate" is not null' ;

				break;
			
			case 'Synced':
				$where.=' and "SyncDate" is not null' ;

				break;
			case 'Unsynced':
				$where.=' and "SyncDate" is null' ;

				break;
			
			default:
				$where.=' and "FinishedDate" is null' ;
				break;
		}


		switch ($this->MappingMeta->SubProduct) {
			case 'RES':{


				$trx = $this->query('SELECT * FROM "ExtTransaction" where "BranchID" = :id and "MainID" = :mid and 
					"ProductID" = :pid and to_char("Date", \'yyyy-mm-dd\') = :date
					'.$where.'
					order by "Date" desc
					',
					[
						"id" => @$this->_token_detail->BranchID,
						"mid" => @$this->_token_detail->MainID,
						"pid" => $this->MappingMeta->SubProduct,
						"date" => @$this->request["Date"]
					]
				);

				$trx_id = $this->extract_column($trx, "ExtTransactionID", [0]);
				if($this->request["Status"]==="Unsynced"){
					$this->query('UPDATE "ExtTransaction" set "SyncDate" = :date where "ExtTransactionID" in ('.implode(',', $trx_id).') ', 
						[
							"date"=>$this->now()->full_time
						]
					);
				}


				$trx_dtl = $this->query('SELECT * from "ExtTransactionDetail" where "ExtTransactionID" in ('.implode(',', $trx_id).') ');
				$trx_dtl_id = $this->extract_column($trx_dtl, "ExtTransactionDetailID", [0]);

				$trx_mod = $this->query('SELECT * from "ExtTransactionModifier" where "ExtTransactionDetailID" in ('.implode(',', $trx_dtl_id).') ');
				$this->map_record($trx_dtl,"Modifiers", "ExtTransactionDetailID", $trx_mod);
				foreach ($trx_dtl as $key => $value) {
					if(@$value->ExtTransactionID==null){
						unset($trx_dtl[$key]);
					}
				}
				$this->map_record($trx,"Items", "ExtTransactionID", $trx_dtl);




				$this->response->Transactions = $trx;
				break;
			}
			default:
				# code...
				break;
		}




		$this->reset_db();
		$this->render(true);
	}


	public function fetch_internal(){

		$this->db  = $this->request["ProductID"];

		$rules = [
			'Date' => 'required',
		];
		//standar validator
		$this->validator($rules);

		$where = "";
		$rid = $this->coalesce(@$this->request["ExtTransactionID"], -1);
		switch (@$this->request["Status"]) {
			case 'Closed':
				$where.=' and "FinishedDate" is not null and ('.$rid.' = -1 or "ExtTransactionID" = '.$rid.'  ) ' ;

				break;
			
			case 'Synced':
				$where.=' and "SyncDate" is not null and ('.$rid.' = -1 or "ExtTransactionID" = '.$rid.'  )' ;

				break;
			case 'Unsynced':
				$where.=' and "SyncDate" is null and ('.$rid.' = -1 or "ExtTransactionID" = '.$rid.'  )' ;

				break;
			
			default:
				$where.=' and "FinishedDate" is null and ('.$rid.' = -1 or "ExtTransactionID" = '.$rid.'  )' ;
				break;
		}

		$date_filter = 'and to_char("Date", \'yyyy-mm-dd\') = :date';

		$pparam = 
		[
			"id" => @$this->request["BranchID"],
			"mid" => @$this->request["MainID"],
			"pid" => $this->request["SubProduct"],
			"date" => @$this->request["Date"]
		];

		if($rid!=-1){
			$date_filter = "";
			unset($pparam["date"]);

		}

		

		switch ($this->request["SubProduct"]) {
			case 'RES':{


				$trx = $this->query('SELECT * FROM "ExtTransaction" where "BranchID" = :id and "MainID" = :mid and 
					"ProductID" = :pid  
					'.$where.' '.$date_filter.'
					order by "Date" desc 
					', $pparam
				);

				$trx_id = $this->extract_column($trx, "ExtTransactionID", [0]);
				if($this->request["Status"]==="Unsynced"){
					$this->query('UPDATE "ExtTransaction" set "SyncDate" = :date where "ExtTransactionID" in ('.implode(',', $trx_id).') ', 
						[
							"date"=>$this->now()->full_time
						]
					);
				}


				$trx_dtl = $this->query('SELECT * from "ExtTransactionDetail" where "ExtTransactionID" in ('.implode(',', $trx_id).') ');
				$trx_dtl_id = $this->extract_column($trx_dtl, "ExtTransactionDetailID", [0]);

				$trx_mod = $this->query('SELECT * from "ExtTransactionModifier" where "ExtTransactionDetailID" in ('.implode(',', $trx_dtl_id).') ');
				$this->map_record($trx_dtl,"Modifiers", "ExtTransactionDetailID", $trx_mod);
				foreach ($trx_dtl as $key => $value) {
					if(@$value->ExtTransactionID==null){
						unset($trx_dtl[$key]);
					}
				}
				$this->map_record($trx,"Items", "ExtTransactionID", $trx_dtl);

				if($rid!=-1){
					$this->response->Transaction = @$trx[0];
				}else{
					$this->response->Transactions = @$trx;
				}


				break;
			}
			default:
				# code...
				break;
		}




		$this->reset_db();
		$this->render(true);
	}


	

	public function close_transaction(){

		$this->db  = $this->request["ProductID"];

		$rules = [
			'Status' => 'required',
			'GeneratedID' => 'required',
		];
		//standar validator
		$this->validator($rules);

		switch ($this->request["SubProduct"]) {
			case 'RES':{
				$this->query('UPDATE "ExtTransaction" set "FinishedDate" = :date,"OrderID" = :GeneratedID,  "TransactionStatus" = :Status where "ExtTransactionID" = :id 
					and "BranchID" = :BranchID and "MainID" = :MainID and "ProductID" = :ProductID
					and "FinishedDate" is null ', 
					[
						"date"=>$this->coalesce(@$this->request["FinishedDate"], $this->now()->full_time),
						"id"=>$this->request["ExtTransactionID"],
						"BranchID" => @$this->request["BranchID"],
						"MainID" => @$this->request["MainID"],
						"GeneratedID" => @$this->request["GeneratedID"],
						"Status" => @$this->request["Status"],
						"ProductID" => @$this->request["SubProduct"],
					]
				);

				$this->response->FinishedDate = @$this->query(
														'SELECT "FinishedDate" from "ExtTransaction" where "ExtTransactionID" = :id '
														, 
														["id"=>$this->request["ExtTransactionID"]
													])[0]->FinishedDate;
				break;
			}
			default:
				# code...
				break;
		}


		$this->reset_db();
		$this->render(true);
	}



	public function test($token){
		$req = '{"_BranchID":35,"_MainID":55,"Date":"2020-10-14 10:33:37","ExtCustomerID":"1081","Name":"GrabFood","Address":"","Phone":"","Email":"","HandlerID":"1081","GrandTotal":200000,"Items":[{"ItemID":"141421","ItemName":"","Price":200000,"Qty":1,"Modifiers":[]}]}';
		$this->_token_detail->ProductID = "OpenTransaction";
		$req = json_decode($req);
		$req = json_decode(json_encode($req), true);
		$this->request = $req;

		$this->db  = $this->_token_detail->ProductID;

		$this->_token_detail->BranchID  = $this->coalesce($this->request["_BranchID"], @$this->_token_detail->BranchID);
		$this->_token_detail->MainID  = $this->coalesce($this->request["_MainID"], @$this->_token_detail->MainID);


		$rules = [
			'Date' => 'required'		
		];
		//standar validator
		$this->validator($rules);
		$ext_trans_id = 0;
		$this->MappingMeta = new \stdClass();
		$this->MappingMeta->SubProduct  = "RES";
		$dbtoken = [];
		switch ($this->MappingMeta->SubProduct) {
			case 'RES':{
					$ex = $this->query('SELECT * 
						from "ExtCustomer" ec 
						join "Handler" h on h."ExtCustomerID" = ec."ExtCustomerID"
						where ec."ExtCustomerID" = :id and ec."ExtensionID" = :eid
						and h."HandlerID" = :hid
						',[
						"id"=>@$this->request["ExtCustomerID"],
			 			"eid"=> "GrabFood",
						"hid"=>@$this->request["HandlerID"],
					]);

					if(count($ex)==0){
						$this->add_error("CH", "CH", "Customer/Handler ID is Invalid");	
					}
					$this->render();

					$trans = [
						"Date" => @$this->request["Date"],
						"Name" => @$this->request["Name"],
						"ExtCustomerID" => @$this->request["ExtCustomerID"],
						"Address" => @$this->request["Address"],
						"Phone" => @$this->request["Phone"],
						"Email" => @$this->request["Email"],
						"GrandTotal" => @$this->request["GrandTotal"],
						"HandlerID" => @$this->request["HandlerID"],
						"CreatedDate" => @$this->now()->full_time,
						"ProductID" => @$this->MappingMeta->SubProduct,
						"BranchID" => @$this->_token_detail->BranchID,
						"MainID" => @$this->_token_detail->MainID
					];

					$this->db  = $this->MappingMeta->SubProduct;

					$modifier  = $this->query('
					SELECT m."MenuID", mi."ModifierItemID"
											From "MenuModifier" mm
											join "Menu" m on m."MenuID" = mm."MenuID"
											left join "ModifierGroup" mg on mg."ModifierGroupID" = mm."ModifierGroupID"
											left join "ModifierItem" mi on mg."ModifierGroupID" = mi."ModifierGroupID"
					
											AND (mi."Archived" is null or mi."Archived" = \'N\')
											AND (mg."Archived" is null or mg."Archived" = \'N\')
											where m."BranchID" = :BranchID and  m."RestaurantID" = :MainID  
											order by m."MenuCode"
						',
						[
							"BranchID"=> $this->_token_detail->BranchID,
							"MainID"=> $this->_token_detail->MainID
						]
					);
					$menu  = $this->query('
					SELECT m."MenuID" from "Menu" m
											where m."BranchID" = :BranchID and  m."RestaurantID" = :MainID  
											AND (m."Archived" is null or m."Archived" = \'N\')
											order by m."MenuCode"
						',
						[
							"BranchID"=> $this->_token_detail->BranchID,
							"MainID"=> $this->_token_detail->MainID
						]
					);
					$menu_id = $this->extract_column($menu, "MenuID", []);
					$mod_id = $this->extract_column($modifier, "ModifierItemID", []);

					$this->db  = $this->_token_detail->ProductID;

					$this->start();
					$ext_trans_id = $this->upsert("ExtTransaction", $trans);
					foreach ($this->request["Items"] as $item) {
						$dtl = [
							"ExtTransactionID" => @$ext_trans_id,
							"ItemID" => @$item["ItemID"],
							"ItemName" => @$item["ItemName"],
							"Price" => @$item["Price"],
							"Qty" => @$item["Qty"]
						];
						if(!in_array(@$item["ItemID"], $menu_id)){
							$this->add_error("Item", "Item", "Item ID {$item["ItemID"]} is Invalid");	
						}
						$this->render();
						$ext_dtl_id = $this->upsert("ExtTransactionDetail", $dtl);


						if(is_array(@$item["Modifiers"])){

							foreach ($item["Modifiers"] as $mod) {
								$mdtl = [
									"ExtTransactionDetailID" => @$ext_dtl_id,
									"ModifierID" => @$mod["ModifierID"],
									"ModifierName" => @$mod["ModifierName"],
									"Price" => @$mod["Price"],
								];

								if(!in_array(@$mod["ModifierID"], $mod_id)){
									$this->add_error("Modifier", "Modifier", "Modifier ID {$mod["ModifierID"]} is Invalid");	
								}
								$this->render();
								$this->upsert("ExtTransactionModifier", $mdtl);

							}
						}
					}

					$this->end();	
					$this->db = "RES";
					$std = new \stdClass();
					$std->Plattform = "iOS";
					$std->DeviceToken = $token;
					$dbtoken[]= $std;

				break;
			}
			default:
				# code...
				break;
		}

		$this->db  = $this->_token_detail->ProductID;


		$this->response->push_response = [];
		foreach($dbtoken as $t){
			
			if($t->Plattform=="iOS"){
				$options = [
					'key_id' => env('APN_KEY'), // The Key ID obtained from Apple developer account
					'team_id' => env('APN_TEAM'), // The Team ID obtained from Apple developer account
					'app_bundle_id' => env('APN_BUNDLE'), // The bundle ID for app obtained from Apple developer account
					'private_key_path' => storage_path(env('APN_P8')), // Path to private key
					'private_key_secret' => null // Private key secret
				];
		
				$authProvider = AuthProvider\Token::create($options);
		
				$alert = Alert::create()->setTitle("New Order");
				$alert = $alert->setBody("Grabfood new order");
		
				$payload = Payload::create()->setAlert($alert);
		
				//set notification sound to default
				$payload->setSound('default');
		
				//add custom value to your notification, needs to be customized
				$payload->setCustomValue('ExtTransactionID', $ext_trans_id);
		
				$deviceTokens = [$t->DeviceToken];
				$notifications = [];
				foreach ($deviceTokens as $deviceToken) {
					$notifications[] = new Notification($payload,$deviceToken);
				}
		
				$client = new Client($authProvider, $production = false);
				$client->addNotifications($notifications);
		
				$pResponses = $client->push(); 
				foreach($pResponses as $pr){
					$this->response->push_responsep[] = $pr;
				}
		
			}


		}

		

	


		$this->response->ExtTransactionID = $ext_trans_id;

		$this->reset_db();
		$this->render(true);
	}


}