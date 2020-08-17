<?php


namespace App\Persistance\ModelsEloquant\Secretary;


use Illuminate\Database\Eloquent\Model;

class SecretaryOnOrganization extends Model
{
    public $timestamps = false;
    protected $table = "secretary_on_organization";
    protected $primaryKey = "secretary_on_organization_id";

    protected $fillable = array(
        "organization_id",
        "user_id"
    );
}