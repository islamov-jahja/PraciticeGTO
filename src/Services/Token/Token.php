<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 04.11.2019
 * Time: 1:53
 */

namespace App\Services\Token;
use \Firebase\JWT\JWT;
use Monolog\Logger;

class Token
{
    public static $key;

    public static function getEncodedToken(array $tokenData):string
    {
        return JWT::encode($tokenData, Token::$key);
    }

    public static function getDecodedToken(string $token)
    {
        return JWT::decode($token, Token::$key, array('HS256'));
    }

    public static function getEncodedPassword(string $password)
    {
        return crypt($password, Token::$key);
    }

    public static function isOldToken($tokenInArray):bool
    {
        $leeway = $tokenInArray['liveTime'];

        $tokenDate = new \DateTime($tokenInArray['addedTime']);
        $tokenDate->add(new \DateInterval('PT'.$leeway.'S'));

        $newDate = (new \DateTime())->setTimezone(new \DateTimeZone('europe/moscow'));

        if ($newDate->format('Y-m-d H:i:s') > $tokenDate->format('Y-m-d H:i:s')){
            return true;
        }


        return false;
    }
}