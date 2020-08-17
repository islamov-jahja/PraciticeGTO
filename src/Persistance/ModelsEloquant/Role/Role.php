<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 03.11.2019
 * Time: 21:37
 */

namespace App\Persistance\ModelsEloquant\Role;


use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public $timestamps = false;
    protected $table = "role";
    protected $primaryKey = "role_id";

    protected $fillable = array(
        "name_of_role"
    );
}