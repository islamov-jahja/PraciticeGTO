<?php


namespace App\Persistance\ModelsEloquant\Trial;


use Illuminate\Database\Eloquent\Model;

class TrialInEvent extends Model
{
    public $timestamps = false;
    protected $table = "trial_in_event";
    protected $primaryKey = "trial_in_event_id";

    protected $fillable = array(
        "trial_id",
        "event_id",
        "sport_object_id",
        "start_date_time"
    );
}