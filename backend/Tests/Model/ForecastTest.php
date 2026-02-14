<?php

namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Forecast;

class ForecastTest extends TestCase
{
    public function testGetData() {
        /*
         * tests:
         * - Test that forecast is returned in the correct format
         */
        $testForecast = Forecast::sellerWeeklyForecast(1, "", "", "00:00", "24:00", "0", "100");
        self::assertTrue(is_int($testForecast['AvgMondayCollected']));
        self::assertTrue(is_int($testForecast['AvgTuesdayCollected']));
        self::assertTrue(is_int($testForecast['AvgWednesdayCollected']));
        self::assertTrue(is_int($testForecast['AvgThursdayCollected']));
        self::assertTrue(is_int($testForecast['AvgFridayCollected']));
        self::assertTrue(is_int($testForecast['AvgSaturdayCollected']));
        self::assertTrue(is_int($testForecast['AvgSundayCollected']));
        self::assertTrue(is_int($testForecast['AvgMondayNoShow']));
        self::assertTrue(is_int($testForecast['AvgTuesdayNoShow']));
        self::assertTrue(is_int($testForecast['AvgWednesdayNoShow']));
        self::assertTrue(is_int($testForecast['AvgThursdayNoShow']));
        self::assertTrue(is_int($testForecast['AvgFridayNoShow']));
        self::assertTrue(is_int($testForecast['AvgSaturdayNoShow']));
        self::assertTrue(is_int($testForecast['AvgSundayNoShow']));
    }
}