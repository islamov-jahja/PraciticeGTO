<?php


namespace App\Persistance\ModelsEloquant\Trial;


use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    public $timestamps = false;
    protected $table = "version_standard";
    protected $primaryKey = "id_version_standard";

    protected $fillable = array(
        "version",
        "path_file",
        "date_update"
    );
}