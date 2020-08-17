<?php

namespace App\Persistance\ModelsEloquant\TeamLead;
use Illuminate\Database\Eloquent\Model;

class TeamLead extends Model
{
    public $timestamps = false;
    protected $table = "team_lead";
    protected $primaryKey = "team_lead_id";

    protected $fillable = array(
        "team_id",
        "user_id"
    );
}