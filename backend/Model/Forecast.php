<?php

namespace TTE\App\Model;

include '../Dataset/forecast.csv';

class Forecast
{
    public static function getForecastInformation() : array {
        // Open file and prepare array
        $records = array_map('str_getcsv', file(__DIR__ . '/../Dataset/forecast.csv'));
        return $records;
    }

    public static function getData() : array {
        // Open file and prepare array
        $records = array_map('str_getcsv', file(__DIR__ . '/../Dataset/forecast.csv'));
        return $records;
    }

    public static function getBundleData() : array {
        // Open file and prepare array
        $records = array_map('str_getcsv', file(__DIR__ . '/../Dataset/forecast.csv'));
        return $records;
    }

    public static function forcastWeeklyReservationNoShow(int $sellerID, string $category, string $weather, string $startTime, string $endTime, int $minDiscount, int $maxDiscount) : array {
        // Load the data
        $forecastData = Forecast::getForecastInformation();
        $linkData = Forecast::getData();
        $bundleData = Forecast::getBundleData();

        $data = array();
        foreach ($forecastData as $dataPoint) {
            $bundle = $bundleData[$dataPoint[0]];

            if($bundle[2] == $sellerID) {
                // get values from dataset
                $dpDate = $dataPoint[2];
                $dpCategory = $bundle[5];
                $dpWeather = $dataPoint[1];

                // Convert the time in form "XX:XX" into integer form
                $dpst = explode(":", $bundle[9]);
                $dpStartTime = $dpst[0] . $dpst[1];

                $dpet = explode(":", $bundle[10]);
                $dpEndTime = $dpet[0] . $dpet[1];

                // Calculate discount on bundle to the nearest 10%
                $dpDiscount = (int)round((($bundle[7] - $dpStartTime[8]) / $bundle[7] * 100), -1);

                // Discount cant be 100 or 0
                if ($dpDiscount == 0) $dpDiscount = 10;
                if ($dpDiscount == 100) $dpDiscount = 90;

                $data
            }
        }

        $totalcollected = array(
            "Monday" => 0,
            "Tuesday" => 0,
            "Wednesday" => 0,
            "Thursday" => 0,
            "Friday" => 0,
            "Saturday" => 0,
            "Sunday" => 0
        );

        $totalNoShow = array(
            "Monday" => 0,
            "Tuesday" => 0,
            "Wednesday" => 0,
            "Thursday" => 0,
            "Friday" => 0,
            "Saturday" => 0,
            "Sunday" => 0
        );

        $daysCounted = array(
            "Monday" => 0,
            "Tuesday" => 0,
            "Wednesday" => 0,
            "Thursday" => 0,
            "Friday" => 0,
            "Saturday" => 0,
            "Sunday" => 0
        );

        // Calculate average amount of collections & no-shows for each day of the week
        $i = 0;
        foreach($data as $row) {
            // Make sure to skip the first row as this does not contain data
            if($i != 0) {
                // Split row time into start time and end time
                $time = explode("-", $row[3]);
                $startHourMins = explode(":", $time[0]);
                $endHourMins = explode(":", $time[1]);

                // Remove the % from the discount
                $discount = explode("%", $row[4]);
                if(
                    ($row[1] == $category || $category == "")
                    && ($row[2] == $weather || $weather == "")
                    && (int)$startHourMins[0] >= $startTime
                    && (int)$endHourMins[0] <= $endTime
                    && (int)$discount[0] >= $minDiscount
                    && (int)$discount[0] <= $maxDiscount
                ) {
                    // Check what day it is and add the stats to that day
                    switch ($row[0]) {
                        case "Monday":
                            $totalcollected["Monday"] += $row[5] - $row[6];
                            $totalNoShow["Monday"] += $row[6];
                            $daysCounted["Monday"] += 1;
                            break;
                        case "Tuesday":
                            $totalcollected["Tuesday"] += $row[5] - $row[6];
                            $totalNoShow["Tuesday"] += $row[6];
                            $daysCounted["Tuesday"] += 1;
                            break;
                        case "Wednesday":
                            $totalcollected["Wednesday"] += $row[5] - $row[6];
                            $totalNoShow["Wednesday"] += $row[6];
                            $daysCounted["Wednesday"] += 1;
                            break;
                        case "Thursday":
                            $totalcollected["Thursday"] += $row[5] - $row[6];
                            $totalNoShow["Thursday"] += $row[6];
                            $daysCounted["Thursday"] += 1;
                        case "Friday":
                            $totalcollected["Friday"] += $row[5] - $row[6];
                            $totalNoShow["Friday"] += $row[6];
                            $daysCounted["Friday"] += 1;
                            break;
                        case "Saturday":
                            $totalcollected["Saturday"] += $row[5] - $row[6];
                            $totalNoShow["Saturday"] += $row[6];
                            $daysCounted["Saturday"] += 1;
                            break;
                        case "Sunday":
                            $totalcollected["Sunday"] += $row[5] - $row[6];
                            $totalNoShow["Sunday"] += $row[6];
                            $daysCounted["Sunday"] += 1;
                            break;
                    }
                }
            }
            $i += 1;
        }

        // Check if any of the days = 0 and set to 1 so don't divide by 0
        if($daysCounted["Monday"] == 0) $daysCounted["Monday"] = 1;
        if($daysCounted["Tuesday"] == 0) $daysCounted["Tuesday"] = 1;
        if($daysCounted["Wednesday"] == 0) $daysCounted["Wednesday"] = 1;
        if($daysCounted["Thursday"] == 0) $daysCounted["Thursday"] = 1;
        if($daysCounted["Friday"] == 0) $daysCounted["Friday"] = 1;
        if($daysCounted["Saturday"] == 0) $daysCounted["Saturday"] = 1;
        if($daysCounted["Sunday"] == 0) $daysCounted["Sunday"] = 1;

        // Construct weekly forecast array
        $weeklyForecast = array(
            "AvgMondayCollected" => (int)round($totalcollected["Monday"] / $daysCounted["Monday"]),
            "AvgTuesdayCollected" => (int)round($totalcollected["Tuesday"] / $daysCounted["Tuesday"]),
            "AvgWednesdayCollected" => (int)round($totalcollected["Wednesday"] / $daysCounted["Wednesday"]),
            "AvgThursdayCollected" => (int)round($totalcollected["Thursday"] / $daysCounted["Thursday"]),
            "AvgFridayCollected" => (int)round($totalcollected["Friday"] / $daysCounted["Friday"]),
            "AvgSaturdayCollected" => (int)round($totalcollected["Saturday"] / $daysCounted["Saturday"]),
            "AvgSundayCollected" => (int)round($totalcollected["Sunday"] / $daysCounted["Sunday"]),
            "AvgMondayNoShow" => (int)round($totalNoShow["Monday"] / $daysCounted["Monday"]),
            "AvgTuesdayNoShow" => (int)round($totalNoShow["Tuesday"] / $daysCounted["Tuesday"]),
            "AvgWednesdayNoShow" => (int)round($totalNoShow["Wednesday"] / $daysCounted["Wednesday"]),
            "AvgThursdayNoShow" => (int)round($totalNoShow["Thursday"] / $daysCounted["Thursday"]),
            "AvgFridayNoShow" => (int)round($totalNoShow["Friday"] / $daysCounted["Friday"]),
            "AvgSaturdayNoShow" => (int)round($totalNoShow["Saturday"] / $daysCounted["Saturday"]),
            "AvgSundayNoShow" => (int)round($totalNoShow["Sunday"] / $daysCounted["Sunday"]),
        );

        return $weeklyForecast;
    }
}


