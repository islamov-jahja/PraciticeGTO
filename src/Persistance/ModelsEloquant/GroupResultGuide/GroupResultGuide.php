<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.10.2019
 * Time: 23:35
 */

namespace App\Persistance\ModelsEloquant\GroupResultGuide;


use Illuminate\Database\Eloquent\Model;

class GroupResultGuide extends Model
{
    public $timestamps = false;
    protected $table = "age_category";
    protected $primaryKey = "id_group_result_guide";
    protected $fillable = array(
        "id_age_category",
        "necessarily",
        "id_group_in_age_category"
    );
}