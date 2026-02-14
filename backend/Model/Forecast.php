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
        $records = array_map('str_getcsv', file(__DIR__ . '/../Dataset/testData.csv'));
        return $records;
    }

    public static function getBundleData() : array {
        // Open file and prepare array
        $records = array_map('str_getcsv', file(__DIR__ . '/../Dataset/bundles.csv'));
        return $records;
    }

    public static function sellerWeeklyForecast(int $sellerID, string $category, string $weather, string $startTime, string $endTime, int $minDiscount, int $maxDiscount) : array {
        // Load forecast predictions
        $forecastData = Forecast::getForecastInformation();
        // Load the link data
        $linkData = Forecast::getData();
        array_shift($linkData);
        // Load the bundle data
        $bundleData = Forecast::getBundleData();

        // create array of seller data to forecast
        $data = array();
        foreach ($linkData as $dataPoint) {
            // Get the bundle related to
            $bundle = $bundleData[$dataPoint[0]];

            // Check if the bundle belongs to the logged in seller
            if($bundle[2] == $sellerID || $sellerID == -1) {
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
                $dpDiscount = (int)round((((int)$bundle[7] - (int)$dpStartTime[8]) / (int)$bundle[7] * 100), -1);

                // Discount cant be 100 or 0
                if ($dpDiscount == 0) $dpDiscount = 10;
                if ($dpDiscount == 100) $dpDiscount = 90;

                // Create record
                $dpData = array($dpDate, $dpCategory, $dpWeather, $dpStartTime, $dpEndTime, $dpDiscount);

                // Add it to the array of data
                array_push($data, $dpData);
            }
        }

        // Give each data piece it's predicted values
        $j = 0;
        foreach ($data as $datum) {
            foreach ($forecastData as $dataPoint) {
                // Remove % from the discount
                $percentageDiscount =  explode("%", $dataPoint[4])[0];

                // Separate times
                $times = explode("-", $dataPoint[3]);
                // Convert times to integer form
                $dpStart = explode(":", $times[0]);
                $dpStartTime = $dpStart[0] . $dpStart[1];

                $dpEnd = explode(":", $times[1]);
                $dpEndTime = $dpEnd[0] . $dpEnd[1];

                // Compare to the datum
                if(
                    $datum[0] == $dataPoint[0]
                    && $datum[1] == $dataPoint[1]
                    && $datum[2] == $dataPoint[2]
                    && $datum[3] == $dpStartTime
                    && $datum[4] == $dpEndTime
                    && $datum[5] == $percentageDiscount
                ) {
                    // If it matches, then give the datum matching forecast information
                    $data[$j][6] = $dataPoint[5];
                    $data[$j][7] = $dataPoint[6];
                    break;
                }
            }
            $j += 1;
        }

        // prepare collected array
        $collected = array(
            "Monday" => 0,
            "Tuesday" => 0,
            "Wednesday" => 0,
            "Thursday" => 0,
            "Friday" => 0,
            "Saturday" => 0,
            "Sunday" => 0
        );

        // prepare no-show array
        $totalNoShow = array(
            "Monday" => 0,
            "Tuesday" => 0,
            "Wednesday" => 0,
            "Thursday" => 0,
            "Friday" => 0,
            "Saturday" => 0,
            "Sunday" => 0
        );

        // prepare days counted array
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
            if(
                // Check if datum needs to be filtered out
                ($row[1] == $category || $category == "")
                && ($row[2] == $weather || $weather == "")
                && (int)$row[3] >= $startTime
                && (int)$row[4] <= $endTime
                && (int)$row[5] >= $minDiscount
                && (int)$row[5] <= $maxDiscount
            ) {
                // Check what day it is and add the stats to that day & increment days
                switch ($row[0]) {
                    case "Monday":
                        $collected["Monday"] += $row[5] - $row[6];
                        $totalNoShow["Monday"] += $row[6];
                        $daysCounted["Monday"] += 1;
                        break;
                    case "Tuesday":
                        $collected["Tuesday"] += $row[5] - $row[6];
                        $totalNoShow["Tuesday"] += $row[6];
                        $daysCounted["Tuesday"] += 1;
                        break;
                    case "Wednesday":
                        $collected["Wednesday"] += $row[5] - $row[6];
                        $totalNoShow["Wednesday"] += $row[6];
                        $daysCounted["Wednesday"] += 1;
                        break;
                    case "Thursday":
                        $collected["Thursday"] += $row[5] - $row[6];
                        $totalNoShow["Thursday"] += $row[6];
                        $daysCounted["Thursday"] += 1;
                    case "Friday":
                        $collected["Friday"] += $row[5] - $row[6];
                        $totalNoShow["Friday"] += $row[6];
                        $daysCounted["Friday"] += 1;
                        break;
                    case "Saturday":
                        $collected["Saturday"] += $row[5] - $row[6];
                        $totalNoShow["Saturday"] += $row[6];
                        $daysCounted["Saturday"] += 1;
                        break;
                    case "Sunday":
                        $collected["Sunday"] += $row[5] - $row[6];
                        $totalNoShow["Sunday"] += $row[6];
                        $daysCounted["Sunday"] += 1;
                        break;
                }
            }
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
            "AvgMondayCollected" => (int)round($collected["Monday"] / $daysCounted["Monday"]),
            "AvgTuesdayCollected" => (int)round($collected["Tuesday"] / $daysCounted["Tuesday"]),
            "AvgWednesdayCollected" => (int)round($collected["Wednesday"] / $daysCounted["Wednesday"]),
            "AvgThursdayCollected" => (int)round($collected["Thursday"] / $daysCounted["Thursday"]),
            "AvgFridayCollected" => (int)round($collected["Friday"] / $daysCounted["Friday"]),
            "AvgSaturdayCollected" => (int)round($collected["Saturday"] / $daysCounted["Saturday"]),
            "AvgSundayCollected" => (int)round($collected["Sunday"] / $daysCounted["Sunday"]),
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


