<?php

namespace App\Persistance\ModelsEloquant\Result;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    public $timestamps = false;
    protected $table = "result_on_trial_in_event";
    protected $primaryKey = "result_on_trial_in_event_id";

    protected $fillable = array(
        "trial_in_event_id",
        "user_id",
        "id_result_guide",
        "first_result",
        "second_result",
        "badge"
    );
}