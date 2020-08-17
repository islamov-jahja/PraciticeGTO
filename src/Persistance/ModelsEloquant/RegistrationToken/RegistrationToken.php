<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 03.11.2019
 * Time: 21:39
 */

namespace App\Persistance\ModelsEloquant\RegistrationToken;


use Illuminate\Database\Eloquent\Model;

class RegistrationToken extends Model
{
    public $timestamps = false;
    protected $table = "registration_token";
    protected $primaryKey = "registration_token_id";

    protected $fillable = array(
        "token",
        "dateTimeToDelete"
    );
}