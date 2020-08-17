<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 03.11.2019
 * Time: 21:34
 */

namespace App\Persistance\ModelsEloquant\User;


use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public $timestamps = false;
    protected $table = "user";
    protected $primaryKey = "user_id";

    protected $fillable = array(
        "uid",
        "name",
        "login",
        "password",
        "email",
        "role_id",
        'gender',
        'date_of_birth',
        "is_activity",
        "registration_date",
    );
}