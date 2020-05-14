<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\Meta as MetaInterface;
use DB;

class Meta implements MetaInterface {
    
    
	public function getDataTableTransaction($table, $display, $column, $perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param, $join = null){
		$offset = $start;
		$result = DB::table($table)->select($display)->where($table.'.BranchID', $param->BranchID)->where($table.'.BrandID', $param->MainID)->distinct();
        if($join != null){
            foreach($join as $join){
                if($join[0] == 'join')
                    $result = $result->join($join[1], $join[2], $join[3], $join[4]);
                elseif($join[0] == 'rightJoin')
                    $result = $result->rightJoin($join[1], $join[2], $join[3], $join[4]);
                elseif($join[0] == 'leftJoin')
                    $result = $result->leftJoin($join[1], $join[2], $join[3], $join[4]);
            }
        }
        if(!empty($keyword)){
        	$result = $result->where(function ($query) use($keyword){
                for($i = 0; $i < count($column);$i++){
                    if($i = 0)
                        $query->where(DB::raw('lower(trim("'.$column[$i].'"::varchar))'),'like',"'%".$keyword."%'");
                    else
        		 	    $query->orWhere(DB::raw('lower(trim("'.$column[$i].'"::varchar))'),'like','%'.$keyword.'%');
                }
        	});	
        }
        $totalFiltered = $result->distinct($orderBy)->count($orderBy);
        $maxPage = ceil($totalFiltered/$perPage);
        if(!empty($orderBy)){
        	if(strtolower($sort) != 'asc' && strtolower($sort) != 'desc') $sort = 'asc';
        	$result = $result->orderBy($orderBy,$sort);
        }
        $result = $result->skip($offset)->take($perPage);
		$response = ['recordsFiltered' => $totalFiltered, 'maxPage' => $maxPage, 'data' => $result->get()];
		return $response;
	}
    
    
	public function totalRecords($table, $param){
		$result = DB::table($table)->where('BranchID', $param->BranchID)->where('BrandID', $param->MainID)->where('Archived', null)->count();
		return $result;
	}
    
    
	public function delete($table, $id){
		try{
            $now = collect(\DB::select("Select timezone('Asia/Jakarta', now()) \"ServerTime\""))->first()->ServerTime;
            $data = array(
                'Archived' => $now
            );
			return DB::table($table)->where($table.'ID', $id)->update($data);
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}
    
	public function deleteDetail($table, $id, $column){
		try{
            $now = collect(\DB::select("Select timezone('Asia/Jakarta', now()) \"ServerTime\""))->first()->ServerTime;
            $data = array(
                'Archived' => $now
            );
			return DB::table($table)->where($column, $id)->delete();
		}catch(\Exception $e){
			return ['error'=>true];
		}
	}
    
    public function getLastID(){
        
        $newid = \DB::select( \DB::raw('SELECT lastval() id'))[0]->id;
        return $newid;
    }
}