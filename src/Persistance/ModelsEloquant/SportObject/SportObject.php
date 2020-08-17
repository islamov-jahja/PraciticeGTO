<?php

namespace App\Persistance\ModelsEloquant\SportObject;
use Illuminate\Database\Eloquent\Model;

class SportObject extends Model
{
    public $timestamps = false;
    protected $table = "sport_object";
    protected $primaryKey = "sport_object_id";

    protected $fillable = array(
        "organization_id",
        "name",
        "address",
        "description"
    );
}