<?php


namespace App\Persistance\ModelsEloquant\Referee;


use Illuminate\Database\Eloquent\Model;

class RefereeOnTrialInEvent extends Model
{
    public $timestamps = false;
    protected $table = "referee_on_trial_in_event";
    protected $primaryKey = "referee_on_trial_in_event_id";
    protected $fillable = array(
        "trial_in_event_id",
        "user_id"
    );
}