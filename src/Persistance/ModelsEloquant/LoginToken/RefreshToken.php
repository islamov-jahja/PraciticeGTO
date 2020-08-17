<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 05.11.2019
 * Time: 1:56
 */

namespace App\Persistance\ModelsEloquant\LoginToken;


use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    public $timestamps = false;
    protected $table = "refresh_token";
    protected $primaryKey = "refresh_token_id";

    protected $fillable = array(
        "token",
        "email"
    );

}