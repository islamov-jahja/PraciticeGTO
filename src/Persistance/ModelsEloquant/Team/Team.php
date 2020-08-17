<?php

namespace App\Persistance\ModelsEloquant\Team;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    public $timestamps = false;
    protected $table = "team";
    protected $primaryKey = "team_id";

    protected $fillable = array(
        "event_id",
        "name"
    );
}