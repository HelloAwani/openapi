<?php

namespace Service\Http\Controllers\OpenTransaction\v1;

use Illuminate\Http\Request;
use Service\Http\Requests;
use Validator;

class Tunnel extends \Service\Http\Controllers\_Heart
{
    public function __construct(Request $request=null){
        if($request!=null){
            parent::__construct($request);
		}
		$this->api_version =  "v1";
		//$this->enforce_product  = "OpenTransaction";


		$whitelists = explode(',', env("WHITELISTS"));
		if(!in_array($_SERVER["REMOTE_ADDR"], $whitelists)){
			print_r($_SERVER["REMOTE_ADDR"]);
			exit();
		}

	}

	public function fetch_transaction(){
		$this->db = "OpenTransaction";

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



		$trx = $this->query('SELECT 
			h."ExtensionID",
			h."HandlerName", h."ReferenceID",
			et."ExtTransactionID",
			et."ExtCustomerID",
			et."Date",
			et."Name",
			et."Address", et."Phone", et."Email", et."GrandTotal"
			from "ExtTransaction" et
			join "Handler" h on h."HandlerID" = et."HandlerID"
			where et."BranchID" = :bid 
			and et."MainID" = :mid
			and et."ProductID" = :pid
			'.$where.'
			and to_char("Date", \'yyyy-mm-dd\') = :date
			order by "Date" desc
			',
				[
					"bid"=>$this->request["outid"],
					"mid"=>$this->request["mid"],
					"pid"=>$this->request["prid"],
					"date"=>@$this->request["Date"],
				]
			);

		$trx_id = $this->extract_column($trx, "ExtTransactionID", [0]);
		if(@$this->request["Status"]==="Unsynced"){
			/*$this->query('UPDATE "ExtTransaction" set "SyncDate" = :date where "ExtTransactionID" in ('.implode(',', $trx_id).') ', 
				[
					"date"=>$this->now()->full_time
				]
			);*/
		}


		$trx_dtl = $this->query('SELECT * from "ExtTransactionDetail" where "ExtTransactionID" in ('.implode(',', $trx_id).') ');
		$trx_dtl_id = $this->extract_column($trx_dtl, "ExtTransactionDetailID", [0]);

		$trx_mod = $this->query('SELECT * from "ExtTransactionModifier" where "ExtTransactionDetailID" in ('.implode(',', $trx_dtl_id).') ');
		$this->map_record($trx_dtl,"Modifiers", "ExtTransactionDetailID", $trx_mod);
		$this->map_record($trx,"Items", "ExtTransactionID", $trx_dtl);

		$this->response->Transactions = $trx;
		$this->render(true);
	}

	public function update_transaction(){
		$this->db = "OpenTransaction";


		$rules = [
			'ExtTransactionID' => 'required',
			'GeneratedTransactionID' => 'numeric',
		];
		//standar validator
		$this->validator($rules);


		$data["FinishedDate"] = $this->now()->full_time;
		$data["OrderID"] = @$this->request["GeneratedTransactionID"];

		$this->upsert("ExtTransaction", $data, $this->request["ExtTransactionID"]);


		$this->response->FinishedDate = $data["FinishedDate"];
		$this->render(true);
	}



}