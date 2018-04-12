<?php
namespace Service\Repositories\Eloquent;
 
use Service\Interfaces\Meta as MetaInterface;
use DB;

class Meta implements MetaInterface {
    
	public function checkUnique($table, $column, $code, $branchID, $brandID, $id = null){
		try{
			$result = DB::table($table)->where('BranchID',$branchID)->where('Archived', null)->where('BrandID',$brandID)->where($table.'ID', '<>', $id);
              
        	$result = $result->where(function ($query) use($column,$code){
                $query->where(DB::raw('trim("'.$column.'"::varchar)'),$code);
                $query->orWhere(DB::raw('lower(trim("'.$column.'"::varchar))'),$code);
        	});	  
                
            $result = $result->count();
			return $result;
		}catch(\Exception $e){
			return 0;
		}
	}
    
	public function checkUniqueGetID($table, $column, $code, $branchID, $brandID){
		try{
			$result = DB::table($table)->where('BranchID',$branchID)->where('Archived', null)->where('BrandID',$brandID);
              
        	$result = $result->where(function ($query) use($column,$code){
                $query->where(DB::raw('trim("'.$column.'"::varchar)'),$code);
                $query->orWhere(DB::raw('lower(trim("'.$column.'"::varchar))'),$code);
        	});	  
                
            $result = $result->get();
            @$result = @$result[0]->{$table.'ID'};
			return $result;
		}catch(\Exception $e){
			return 0;
		}
	}
    
	public function get($table, $data, $param = null, $join = null, $where = null){
		
            $result = DB::table($table)->select($data);
            
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
            
            if($where != null){
                foreach($where as $where){
                    if($where[0] == 'where')
                        $result = $result->where($where[1], $where[2], $where[3]);
                    if($where[0] == 'whereNotNull')
                        $result = $result->whereNotNull($where[1]);
                    if($where[0] == 'whereIsNull'){
                        $result = $result->where($where[1], $where[2]);
                    }
                }
            }
            if($param != null)
			    $result = $result->where($table.'.BranchID',$param->BranchID)->where($table.'.BrandID',$param->MainID)->where($table.'.Archived', null);
			return $result->get();
		
	}
    
	public function upsert($table, $data, $param, $id = null){
		try{
            if(!empty($id)){
                // update data
                DB::table($table)->where('BranchID',$param->BranchID)->where('BrandID',$param->MainID)->where($table.'ID', $id)->update($data);
                    return $this->find($table, $id);
            }else{
                // insert data
                $insertedData = DB::table($table)->insert($data);
                if(isset($insertedData['error'])){ 
                    if($this->environment != 'live') $errorMsg = $insertedData['message'];
                    else $errorMsg = "Database Error"; 
                    $response = $this->generateResponse(1, $errorMsg, "Database Error");
                }
                return array('ID' => $this->getLastID());
            }
		}catch(\Exception $e){
			return 0;
		}
	}
    
    public function find($table, $id, $first = false){
		try{
            if($first == true){
                return DB::table($table)->where($table.'ID', $id)->get()->first();
            } else {
                return DB::table($table)->where($table.'ID', $id)->get();
            }
		}catch(\Exception $e){
			return ['error'=>true];
		}
    }
    
    public function findDetail($table, $id, $column){
		try{
			return DB::table($table)->where($column, $id)->get();
		}catch(\Exception $e){
			return ['error'=>true];
		}
    }
        
    
	public function getDataTable($table, $display, $column, $perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param, $join = null, $extraWhere = null){
		$offset = $start;
		$result = DB::table($table)->select($display)->where($table.'.BranchID', $param->BranchID)->where($table.'.BrandID', $param->MainID)->where($table.'.Archived', null);
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
        
        if($extraWhere != null){
            foreach($extraWhere as $where){
                $result = $result->where($where[0], $where[1]);
            }
        }
        if(!empty($keyword)){
        	$result = $result->where(function ($query) use($keyword, $column){
                for($i = 0; $i < count($column);$i++){
        		 	$query->orWhere(DB::raw('lower(trim("'.$column[$i].'"::varchar))'),'like','%'.strtolower($keyword).'%');
                }
        	});	
        }
        $totalFiltered = $result->count();
        $maxPage = ceil($totalFiltered/$perPage);
        if(!empty($orderBy)){
        	if(strtolower($sort) != 'asc' && strtolower($sort) != 'desc') $sort = 'asc';
        	$result = $result->orderBy($orderBy,$sort);
        }
        $result = $result->skip($offset)->take($perPage);
		$response = ['recordsFiltered' => $totalFiltered, 'maxPage' => $maxPage, 'data' => $result->get()];
		return $response;
	}
    
    
    
	public function getDataTableTransaction($table, $display, $column, $perPage = 10, $start = 0, $orderBy = null, $sort = 'asc', $keyword = null, $param, $join = null, $extraWhere = null){
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
        if($extraWhere != null){
            foreach($extraWhere as $where){
                $result = $result->where($where[0], $where[1]);
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