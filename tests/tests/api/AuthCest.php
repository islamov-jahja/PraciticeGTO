<?php 

class AuthCest
{
    public function _before(ApiTester $I)
    {
    }

    // tests
    public function incorrectLogin(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/auth/login', [
            'email' => 'fff@mail.ru',
            'password' => '123456'
        ]);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType([
            'errors' => 'array'
        ]);
    }

    public function tokenInCorrectEnter(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/auth/login', [
            'email' => 'narac35700@emailhost99.com',
            'password' => '123456'
        ]);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType([
            'accessToken' => 'string',
            'refreshToken' => 'string',
            'userId' => 'integer',
            'role' => 'string'
        ]);
    }
}
