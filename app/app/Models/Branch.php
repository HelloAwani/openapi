<?php

namespace Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
	use SoftDeletes;
	
    protected $table = 'Branch';
    protected $fillable = ['BranchName','Address','Contact','Email','MaxDevice','MaxLocalDevice'];
}
