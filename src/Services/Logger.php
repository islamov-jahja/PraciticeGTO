<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 08.11.2019
 * Time: 0:17
 */

namespace App\Services;
use Psr\Http\Message\RequestInterface as Request;

class Logger
{
    public static function WriteLog(Request $request){
        $log = "Method: ".$request->getMethod().'url: '.$request->getUri()->getHost();
        file_put_contents(__DIR__.'/../../Logs/log.txt', $log, FILE_APPEND);
    }
}