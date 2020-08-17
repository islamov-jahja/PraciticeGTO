<?php

namespace App\Persistance\ModelsEloquant\EventParticipant;

use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    public $timestamps = false;
    protected $table = "event_participant";
    protected $primaryKey = "event_participant_id";
    protected $fillable = array(
        "event_id",
        "user_id",
        "team_id",
        "confirmed",
    );
}