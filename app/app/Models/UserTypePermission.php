<?php

namespace Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserTypePermission extends Model
{
    // use SoftDeletes;
	
    protected $table = 'UserTypePermission';
    protected $fillable = ['PermissionID','UserTypeID'];

    public function Permission()
    {
        return $this->belongsTo('Service\Models\Permission','PermissionID','PermissionID');
    }
}
