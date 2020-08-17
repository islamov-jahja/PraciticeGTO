<?php

namespace App\Persistance\ModelsEloquant\LocalAdmin;

use Illuminate\Database\Eloquent\Model;
class LocalAdmin extends Model
{
    public $timestamps = false;
    protected $table = "local_admin";
    protected $primaryKey = "local_admin_id";

    protected $fillable = array(
        "user_id",
        "organization_id"
    );
}