<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.10.2019
 * Time: 12:53
 */

namespace App\Persistance\ModelsEloquant\ResultGuide;


use Illuminate\Database\Eloquent\Model;

class ResultGuide extends Model
{
    public $timestamps = false;
    protected $table = "result_guide";
    protected $primaryKey = "id_result_guide";
    protected $fillable = array(
        "id_age_category",
        "id_trial",
        "gender",
        "results",
        "id_version",
        "id_group_result_guide",
        "result_for_gold",
        "result_for_silver",
        "result_for_bronze"
    );
}