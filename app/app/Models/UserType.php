<?php

namespace Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserType extends Model
{
    use SoftDeletes;
	
    protected $table = 'UserType';
    protected $fillable = ['UserTypeCode','UserTypeName','BrandID','BranchID'];
    public $timestamps = false;
    const DELETED_AT = 'Archived';
    protected $primaryKey = 'UserTypeID';

    public function Permissions()
    {
        return $this->hasMany('Service\Models\UserTypePermission','UserTypeID');
    }
}
