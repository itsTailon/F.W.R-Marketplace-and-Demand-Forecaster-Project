<?php

namespace TTE\App\Helpers;

class TimeTools {

    /**
     * Verifies whether the input is a correctly-formatted (24-hour) time slot string representing a one-hour time slot — i.e. a string in the form 'hh:mm-hh:mm' using the 24-hour time format.
     *
     * Note: the '23:00-00:00' extreme boundary case will yield true for compatibility with the application database.
     *
     * Example A:
     *      Input:  '09:00-10:00'
     *      Output: true
     *      Reason: Correct format, and the time slot is exactly 1 hour long.
     *
     * Example B:
     *      Input:  '09:00-11:00'
     *      Output: false
     *      Reason: The time slot is not exactly 1 hour long.
     *
     * Example C:
     *      Input:  '9:00-10:00'
     *      Output: false
     *      Reason: Required zero padding not present (i.e. '9:00' should be '09:00')
     *
     * Example D:
     *      Input:  '23:00-01:00'
     *      Output: false
     *      Reason: The time slot spans two days
     *
     * Example D:
     *      Input:  '23:00-00:00'
     *      Output: true
     *      Reason: Despite spanning two days, the time slot is deemed valid to maintain compatibility with the app database (allow 24/7 pickups)
     *
     * @param string $slot
     * @return bool
     */
    public static function verifyTimeSlotStringFormat(string $slot): bool {
        // String should be of length 11 (hh:mm-hh:mm)
        if (strlen($slot) < 11) {
            return false; // Input too short to be in the correct format
        }

        // Split string with '-' delimeter
        $times = explode("-", $slot);

        // Splitting by '-' should yield two components/elements (the start and end times)
        if (count($times) != 2) {
            return false; // Too many or too little components
        }

        // Verify the separate time strings
        if (!self::verifyTimeStringFormat($times[0]) || !self::verifyTimeStringFormat($times[0])) {
            return false; // Incorrect formatting
        }

        // Parse time strings (get hour and minute values)
        $start = self::parseTimeString($times[0]);
        $end = self::parseTimeString($times[1]);

        if ($start === null || $end === null) {
            return false;
        }

        // Ensure that the time slot is exactly one hour long, allowing '23:00-00:00' boundary case.
        if ((self::timeAsSecondsFromMidnight($end[0], $end[1]) - self::timeAsSecondsFromMidnight($start[0], $start[1])) !== 3600) {
            // Allow '23:00-00:00' extreme boundary case
            if (!($start == [23, 0] && $end == [0, 0])) {
                return false;
            }
        }

        // Input is valid
        return true;
    }

    /**
     * Verifies whether the input is a correctly-formatted (24-hour) time string (excl. seconds) — i.e. a string in the form 'hh:mm'.
     *
     * @param string $time
     * @return bool
     */
    public static function verifyTimeStringFormat(string $time): bool {
        // String should be of length 5 (hh:mm)
        if (strlen($time) < 5) {
            return false; // Input too short to be in the correct format
        }

        // Split string with ':' delimeter
        $split = explode(":", $time);

        // Splitting by ':' should yield two components/elements (the hour and minute values)
        if (count($split) != 2) {
            return false; // Too many or too little components
        }

        // Ensure that the hour value is an integer (trim leading zero)
        $hourStr = mb_substr($split[0], 0, 2) == "00" ? "0" : ltrim($split[0], "0");
        $hour = filter_var($hourStr, FILTER_VALIDATE_INT);
        if (!is_int($hour)) {
            return false; // Hour value is not an integer
        }

        // Ensure that the minute value is an integer (trim leading zero)
        $minuteStr = mb_substr($split[1], 0, 2) == "00" ? "0" : ltrim($split[1], "0");
        $minute = filter_var($minuteStr, FILTER_VALIDATE_INT);
        if (!is_int($minute)) {
            return false; // Minute value is not an integer
        }

        // Ensure that, if the hour value < 10, the correct padding is used
        if ($hour < 10 && mb_substr($split[0], 0, 1) !== "0") {
            return false; // Hour value not padded with a zero when it should be
        }

        // Ensure that, if the minute value < 10, the correct padding is used
        if ($minute < 10 && mb_substr($split[1], 0, 1) !== "0") {
            return false; // Hour value not padded with a zero when it should be
        }

        // Ensure that the hour value is within the correct range
        if ($hour < 0 || $hour > 23) {
            return false;
        }

        // Ensure that the minute value is within the correct range
        if ($minute < 0 || $minute > 59) {
            return false;
        }

        // Input is valid
        return true;
    }

    /**
     * Parses a (24-hour) time string in the format 'hh:mm' and returns the hours and minutes as an array of two integers (output[0] = hours, output[1] = minutes).
     *
     * Returns null if the time string is invalid. (see TimeTools::verifyTimeStringFormat())
     *
     * @param string $time
     * @return ?array
     */
    public static function parseTimeString(string $time): ?array {
        if (!self::verifyTimeStringFormat($time)) {
            return null;
        }

        // Split into hour and minute values
        $split = explode(":", $time);

        return [
            intval(mb_substr($split[0], 0, 2) == "00" ? "0" : ltrim($split[0], "0")),  // Hour
            intval(mb_substr($split[1], 0, 2) == "00" ? "0" : ltrim($split[1], "0"))   // Minute
        ];
    }

    /**
     * Converts a time, passed as separate (24-hour) hour and minute values, to seconds past midnight. Returns null if invalid value(s) are passed.
     *
     * Example:
     *      Input:  1 (hour), 0 (minute)
     *      Output: 3600
     *
     * @param int $hour
     * @param int $minute
     * @return int|null
     */
    public static function timeAsSecondsFromMidnight(int $hour, int $minute): ?int {
        // Ensure that the hour value is within the correct range
        if ($hour < 0 || $hour > 23) {
            return null;
        }

        // Ensure that the minute value is within the correct range
        if ($minute < 0 || $minute > 59) {
            return null;
        }

        // Calculate seconds from midnight
        return ($hour * 60 * 60) + ($minute * 60);
    }

}