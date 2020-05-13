<?php

namespace Service\Http\Controllers\OpenTransaction\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Master extends \Service\Http\Controllers\_Heart
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->api_version =  "v1";
		$this->enforce_product  = "OpenTransaction";
	}


	public function get(){

		$this->validate_request();
		$this->db  = $this->MappingMeta->SubProduct;
		$this->render();


		switch ($this->MappingMeta->SubProduct) {
			case 'RES':
				
				$this->response->Categories = $this->query('SELECT "CategoryID", "Color", 
					\'https://hellobill-fnb.s3.amazonaws.com/\'||"Image" "Image", 
					"Description", "CategoryCode","CategoryName"
					from "Category" where "BranchID" = :BranchID and  "RestaurantID" = :MainID
					AND ("Archived" is null or "Archived" = \'N\')
					order by "CategoryCode"
					',
						[
							"BranchID"=> $this->_token_detail->BranchID,
							"MainID"=> $this->_token_detail->MainID
						]
				);



				$data = $this->query('SELECT "CategoryID", "MenuID", 
				\'https://hellobill-fnb.s3.amazonaws.com/\'||"MenuImage" "Image", 
				"Color", "MenuCode", "MenuName", "Price", "Description",  
				"TaxExclusive",
				case "UseManualFoodCost" when \'Y\'  then \'1\' else \'0\' END "UseManualFoodCost",
				"ManualFoodCost", 
				"FoodCost" "PerpetualFoodCost"
				from "Menu" where "BranchID" = :BranchID and  "RestaurantID" = :MainID
				AND ("Archived" is null or "Archived" = \'N\')
				order by "MenuCode"
				',
					[
						"BranchID"=> $this->_token_detail->BranchID,
						"MainID"=> $this->_token_detail->MainID
					]
				);
				$this->response->Items = $data;


				$data = $this->query('SELECT 
						"ModifierGroupID",
						"ModifierGroupName",
						"Min",
						"Max"
						from "ModifierGroup" 
						where "BranchID" = :BranchID and  "RestaurantID" = :MainID  AND ("Archived" is null or "Archived" = \'N\')
						order by "ModifierGroupName"
						',
							[
								"BranchID"=> $this->_token_detail->BranchID,
								"MainID"=> $this->_token_detail->MainID
							]
						);
						$id = $this->extract_column($data, "ModifierGroupID");

						$mods  = $this->query('SELECT mg."ModifierGroupID", "ModifierName",  "PriceModifier", "ModifierItemID",
							case mi."RefHQ" when null then  \'0\' else \'1\' end as  "ClonedFromHQ"
							From "ModifierItem" mi
							join "ModifierGroup" mg on mi."ModifierGroupID" = mg."ModifierGroupID"

							where mi."BranchID" = :BranchID and  mi."RestaurantID" = :MainID  AND (mi."Archived" is null or mi."Archived" = \'N\')
							and 
							mg."BranchID" = :BranchID and  mg."RestaurantID" = :MainID  AND (mg."Archived" is null or mg."Archived" = \'N\')

							',
							[
								"BranchID"=> $this->_token_detail->BranchID,
								"MainID"=> $this->_token_detail->MainID
							]
						);


						$menu  = $this->query('SELECT "ModifierGroupID", m."MenuID",m."MenuCode", m."MenuName"
							From "MenuModifier" mm
							join "Menu" m on m."MenuID" = mm."MenuID"
							where m."BranchID" = :BranchID and  m."RestaurantID" = :MainID  AND (m."Archived" is null or m."Archived" = \'N\')
							order by m."MenuCode"
							',
							[
								"BranchID"=> $this->_token_detail->BranchID,
								"MainID"=> $this->_token_detail->MainID
							]
						);


						$this->map_record($data,"Modifiers", "ModifierGroupID", $mods);
						$this->map_record($data,"Items", "ModifierGroupID", $menu);

						$this->response->Modifiers = $data;

				break;
			
			default:
				# code...
				break;
		}


		$this->render(true);


	}
	
	public function outlet(){
		$this->validate_request();
		$this->db  = $this->MappingMeta->SubProduct;
		$this->render();

		$oi = new \stdClass();
		$oi->BranchName = $this->outlet_info->BranchName;
		$oi->Address = $this->outlet_info->Address;
		$oi->Contact = $this->outlet_info->Contact;
		$oi->Description = $this->outlet_info->Description;
       	$this->response->OutletInfo = $oi;


		$this->render(true);

	}


}