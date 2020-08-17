<?php

namespace App\Persistance\ModelsEloquant\Secretary;
use Illuminate\Database\Eloquent\Model;

class Secretary extends Model
{
    public $timestamps = false;
    protected $table = "secretary";
    protected $primaryKey = "secretary_id";

    protected $fillable = array(
        "event_id",
        "user_id"
    );
}