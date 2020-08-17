<?php


namespace App\Persistance\ModelsEloquant\Trial;


use App\Domain\Models\IModel;
use Illuminate\Database\Eloquent\Model;

class TableInEvent extends Model
{
    public $timestamps = false;
    protected $table = "table_in_event";
    protected $primaryKey = "table_in_event_id";

    protected $fillable = array(
        "table_id",
        "event_id"
    );
}