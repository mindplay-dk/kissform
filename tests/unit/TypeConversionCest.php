<?php

namespace mindplay\kissform\Test;

use mindplay\kissform\Fields\DateTimeField;
use mindplay\kissform\Fields\IntField;
use UnitTester;

class TypeConversionCest
{
    public function convertIntegerValue(UnitTester $I)
    {
        $I->testConversion(
            new IntField('value'),
            ["12345" => 12345, "0" => 0],
            ["aaa", [], 123.456]
        );
    }

    public function convertDateTimeValue(UnitTester $I)
    {
        $I->testConversion(
            new DateTimeField('value', 'Europe/Copenhagen', 'Y-m-d H:i:s'),
            ['1975-07-07 00:00:00' => 173919600],
            ["aaa", [], 123.456, "2014-01-01"]
        );
    }
}
