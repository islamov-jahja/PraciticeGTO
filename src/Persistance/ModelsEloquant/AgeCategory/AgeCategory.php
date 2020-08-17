<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.10.2019
 * Time: 8:07
 */

namespace App\Persistance\ModelsEloquant\AgeCategory;
use \Illuminate\Database\Eloquent\Model;

class AgeCategory extends Model
{
    public $timestamps = false;
    protected $table = "age_category";
    protected $primaryKey = "id_age_category";
    protected $fillable = array(
        "name_age_category",
        "min_age",
        "max_age",
        "m_count_tests",
        "m_count_tests_for_gold",
        "m_count_tests_for_silver",
        "m_count_tests_for_bronze",
        "f_count_tests",
        "f_count_tests_for_gold",
        "f_count_tests_for_silver",
        "f_count_tests_for_bronze"
    );
}