<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.10.2019
 * Time: 23:21
 */

namespace App\Persistance\ModelsEloquant\Trial;


use Illuminate\Database\Eloquent\Model;

class Trial extends Model
{
    public $timestamps = false;
    protected $table = "trial";
    protected $primaryKey = "id_trial";

    protected $fillable = array(
        "trial",
        "type_time",
        "id_version_standard"
    );
}