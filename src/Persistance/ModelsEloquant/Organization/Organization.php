<?php

namespace App\Persistance\ModelsEloquant\Organization;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    public $timestamps = false;
    protected $table = "organization";
    protected $primaryKey = "organization_id";
    protected $fillable = array(
        "name",
        "address",
        "leader",
        "phone_number",
        "OQRN",
        "payment_account",
        "branch",
        "bik",
        "correspondent_account"
    );
}