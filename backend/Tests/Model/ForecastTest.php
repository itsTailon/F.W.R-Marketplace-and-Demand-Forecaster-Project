<?php

namespace TTE\App\Tests\Model;

use PHPUnit\Framework\TestCase;
use TTE\App\Model\Forecast;

class ForecastTest extends TestCase
{
    public function testGetData() {
        $bill = Forecast::sellerWeeklyForecast("2", "", "", "00:00", "24:00", "0", "100");
    }
}