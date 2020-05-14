<?php

namespace Service\Http\Controllers\OpenTransaction\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Transaction extends \Service\Http\Controllers\_Heart
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->api_version =  "v1";
		$this->enforce_product  = "OpenTransaction";
	}

	public function submit(){
		$this->validate_request();
		$this->db  = $this->_token_detail->ProductID;

		$rules = [
			'Date' => 'required'		
		];
		//standar validator
		$this->validator($rules);
		$ext_trans_id = 0;
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

					$menu  = $this->query('SELECT m."MenuID", mi."ModifierItemID"
						From "MenuModifier" mm
						join "Menu" m on m."MenuID" = mm."MenuID"
						join "ModifierGroup" mg on mg."ModifierGroupID" = mm."ModifierGroupID"
						join "ModifierItem" mi on mg."ModifierGroupID" = mi."ModifierGroupID"
						where m."BranchID" = :BranchID and  m."RestaurantID" = :MainID  
						AND (m."Archived" is null or m."Archived" = \'N\')
						AND (mi."Archived" is null or mi."Archived" = \'N\')
						AND (mg."Archived" is null or mg."Archived" = \'N\')
						order by m."MenuCode"
						',
						[
							"BranchID"=> $this->_token_detail->BranchID,
							"MainID"=> $this->_token_detail->MainID
						]
					);
					$menu_id = $this->extract_column($menu, "MenuID", []);
					$mod_id = $this->extract_column($menu, "ModifierItemID", []);

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

				break;
			}
			default:
				# code...
				break;
		}


		$this->response->ExtTransactionID = $ext_trans_id;

		$this->reset_db();
		$this->render(true);
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
		switch ($this->request["Status"]) {
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
						"date" => $this->request["Date"]
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


}