<?php


namespace App\Services;


class SQLBuilders
{
    public static function updateBuilder(array $keyAndValues):string
    {
        $sqlUpdatesInArray = [];
        foreach ($keyAndValues as $key => $value){
            $sqlUpdatesInArray[] = $key.'='.$value;
        }

        return implode(',', $sqlUpdatesInArray);
    }
}