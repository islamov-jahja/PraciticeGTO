<?php 

class TableCest
{
    public function _before(ApiTester $I)
    {
    }

    // tests

    public function tableOnNeededStructure(ApiTester $I)
    {
        $I->sendGET('/tables');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType([
            'tableId' => 'integer',
            'name' => 'string'
        ]);
    }
}
