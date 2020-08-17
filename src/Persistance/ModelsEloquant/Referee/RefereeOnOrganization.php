<?php

namespace App\Persistance\ModelsEloquant\Referee;
use Illuminate\Database\Eloquent\Model;

class RefereeOnOrganization extends Model
{
    public $timestamps = false;
    protected $table = "referee_on_organization";
    protected $primaryKey = "referee_on_organization_id";
    protected $fillable = array(
        "organization_id",
        "user_id"
    );
}