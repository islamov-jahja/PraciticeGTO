<?php

namespace App\Persistance\ModelsEloquant\Event;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    public $timestamps = false;
    protected $table = "event";
    protected $primaryKey = "event_id";
    protected $fillable = array(
        "organization_id",
        "name",
        "start_date",
        "expiration_date",
        "description",
        "status"
    );
}