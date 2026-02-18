<?php

namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Forecast;

class ForecastTest extends TestCase
{

    /**
     * test that the count of collected items are correctly spread across the returned arrays
     *
     * @return void
     */
    public function testCountSpread(){
        /*
         * tests:
         * - If the amount of no show / collected is correctly represented in the
         */

        // Prepare dummy data
        $testData = array(
            array("Monday", "", "", "", "", "", "collected"),
            array("Monday", "", "", "", "", "", "collected"),
            array("Monday", "", "", "", "", "", "no-show"),

            array("Tuesday", "", "", "", "", "", "collected"),

            array("Wednesday", "", "", "", "", "", "no-show"),

            array("Thursday", "", "", "", "", "", "collected"),
            array("Thursday", "", "", "", "", "", "collected"),
            array("Thursday", "", "", "", "", "", "collected"),

            array("Saturday", "", "", "", "", "", "collected"),
            array("Saturday", "", "", "", "", "", "collected"),
            array("Saturday", "", "", "", "", "", "collected"),
            array("Saturday", "", "", "", "", "", "collected"),
            array("Saturday", "", "", "", "", "", "no-show"),

            array("Sunday", "", "", "", "", "", "no-show"),
            array("Sunday", "", "", "", "", "", "no-show"),
            array("Sunday", "", "", "", "", "", "no-show"),
        );

        $testCount = Forecast::countSpread($testData);

        $collectCount = $testCount[0];
        $noShowCount = $testCount[1];

        // check in number of 'collected' were correctly distributed
        self::assertTrue($collectCount["Monday"] == 2);
        self::assertTrue($collectCount["Tuesday"] == 1);
        self::assertTrue($collectCount["Wednesday"] == 0);
        self::assertTrue($collectCount["Thursday"] == 3);
        self::assertTrue($collectCount["Friday"] == 0);
        self::assertTrue($collectCount["Saturday"] == 4);
        self::assertTrue($collectCount["Sunday"] == 0);

        // check in number of 'no-show' were correctly distributed
        self::assertTrue($noShowCount["Monday"] == 1);
        self::assertTrue($noShowCount["Tuesday"] == 0);
        self::assertTrue($noShowCount["Wednesday"] == 1);
        self::assertTrue($noShowCount["Thursday"] == 0);
        self::assertTrue($noShowCount["Friday"] == 0);
        self::assertTrue($noShowCount["Saturday"] == 1);
        self::assertTrue($noShowCount["Sunday"] == 3);
    }

    /**
     * test the format that the forecast data is returned
     *
     * @return void
     */
    public function testGetDataFormat() {
        /*
         * tests:
         * - Test that forecast is returned in the correct format
         */
        $testForecast = Forecast::sellerWeeklyForecast(1, "00:00", "24:00", "0", "100");
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