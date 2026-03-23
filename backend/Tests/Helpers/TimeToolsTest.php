<?php

namespace TTE\App\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use TTE\App\Helpers\TimeTools;

class TimeToolsTest extends TestCase {
    public function testTimeAsSecondsFromMidnight() {
        // Test valid input
        $this->assertEquals(3600, TimeTools::timeAsSecondsFromMidnight(1, 0));

        // Test boundary inputs
        $this->assertEquals(0, TimeTools::timeAsSecondsFromMidnight(0, 0));
        $this->assertEquals(86340, TimeTools::timeAsSecondsFromMidnight(23, 59));

        // Test invalid inputs
        $this->assertNull(TimeTools::timeAsSecondsFromMidnight(-1, 0));
        $this->assertNull(TimeTools::timeAsSecondsFromMidnight(24, 0));
        $this->assertNull(TimeTools::timeAsSecondsFromMidnight(1, -1));
        $this->assertNull(TimeTools::timeAsSecondsFromMidnight(1, 60));
    }

    public function testParseTimeString() {
        // Test valid inputs
        $this->assertEquals([10, 1], TimeTools::parseTimeString("10:01"));
        $this->assertEquals([8, 0], TimeTools::parseTimeString("08:00"));


        // Test boundary inputs
        $this->assertEquals([23, 59], TimeTools::parseTimeString("23:59"));
        $this->assertEquals([0, 0], TimeTools::parseTimeString("00:00"));

        // Test invalid inputs
        $this->assertNull(TimeTools::parseTimeString("0:00"));
        $this->assertNull(TimeTools::parseTimeString("9:00"));
        $this->assertNull(TimeTools::parseTimeString("10:0"));
        $this->assertNull(TimeTools::parseTimeString("24:00"));
        $this->assertNull(TimeTools::parseTimeString("10:60"));
    }

    public function testVerifyTimeStringFormat() {
        // Test valid input
        $this->assertTrue(TimeTools::verifyTimeStringFormat("09:25"));

        // Test boundary inputs
        $this->assertTrue(TimeTools::verifyTimeStringFormat("23:59"));
        $this->assertTrue(TimeTools::verifyTimeStringFormat("00:00"));

        // Test invalid inputs
        $this->assertFalse(TimeTools::verifyTimeStringFormat("0:00"));
        $this->assertFalse(TimeTools::verifyTimeStringFormat("9:00"));
        $this->assertFalse(TimeTools::verifyTimeStringFormat("10:0"));
        $this->assertFalse(TimeTools::verifyTimeStringFormat("24:00"));
        $this->assertFalse(TimeTools::verifyTimeStringFormat("10:60"));
    }

    public function testVerifyTimeSlotStringFormat() {
        // Test valid inputs
        $this->assertTrue(TimeTools::verifyTimeSlotStringFormat("10:00-11:00"));
        $this->assertTrue(TimeTools::verifyTimeSlotStringFormat("05:00-06:00"));

        // Test boundary inputs
        $this->assertTrue(TimeTools::verifyTimeSlotStringFormat("00:00-01:00"));
        $this->assertTrue(TimeTools::verifyTimeSlotStringFormat("10:59-11:59"));
        $this->assertTrue(TimeTools::verifyTimeSlotStringFormat("23:00-00:00"));

        // Test invalid inputs
        $this->assertFalse(TimeTools::verifyTimeSlotStringFormat("10:00-12:00"));
        $this->assertFalse(TimeTools::verifyTimeSlotStringFormat("08:00-07:00"));
        $this->assertFalse(TimeTools::verifyTimeSlotStringFormat("00:00-00:00"));
        $this->assertFalse(TimeTools::verifyTimeSlotStringFormat("10:00-10:00"));
        $this->assertFalse(TimeTools::verifyTimeSlotStringFormat("0:00-01:00"));
        $this->assertFalse(TimeTools::verifyTimeSlotStringFormat("08:00-9:00"));
        $this->assertFalse(TimeTools::verifyTimeSlotStringFormat("aa"));
        $this->assertFalse(TimeTools::verifyTimeSlotStringFormat("-1:00-00:00"));
        $this->assertFalse(TimeTools::verifyTimeSlotStringFormat("23:00-24:00"));
        $this->assertFalse(TimeTools::verifyTimeSlotStringFormat("00:60-01:60"));

    }
}