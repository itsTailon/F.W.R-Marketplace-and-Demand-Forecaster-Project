<?php

namespace TTE\App\Model;

include '../Dataset/forecast.csv';

class Forecast
{
    public static function getData() : array {
        // Open file and prepare array
        return array_map('str_getcsv', file(__DIR__ . '/../Dataset/testData.csv'));
    }

    public static function getBundleData() : array {
        // Open file and prepare array
        return array_map('str_getcsv', file(__DIR__ . '/../Dataset/bundles.csv'));
    }

    public static function sellerWeeklyForecast(int $sellerID, string $startTime, string $endTime, int $minDiscount, int $maxDiscount) : array {
        // Load the link data
        $linkData = Forecast::getData();
        array_shift($linkData);
        $numWeeks = (int) array_shift($linkData);

        // Load the bundle data
        $bundleData = Forecast::getBundleData();

        // create array of seller data to forecast
        $data = array();
        foreach ($linkData as $dataPoint) {
            // Get the bundle related to the data
            $bundle = $bundleData[$dataPoint[0]];

            // Check if the bundle belongs to the logged in seller
            if(($bundle[2] == $sellerID || $sellerID == -1) && (($bundle[1] == "expired") || ($bundle[1] == "collected") || ($bundle[1] == "available"))) {
                // get values from dataset
                $dpDate = $dataPoint[2];

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

                if($bundle[1] == "collected" || $bundle[1] == "available")
                    $status = "collected";
                else
                    $status = "no-show";

                // Create record
                $dpData = array($dpDate,"Weather", "catagory", $dpStartTime, $dpEndTime, $dpDiscount, $status);

                // Add it to the array of data
                $data[] = $dpData;
            }
        }

        // Filter out values
        $filteredData = array();
        foreach($data as $row) {
            if(
                // Check if datum needs to be filtered out
                (int)$row[3] >= $startTime
                && (int)$row[4] <= $endTime
                && (int)$row[5] >= $minDiscount
                && (int)$row[5] <= $maxDiscount
            ) {
                $filteredData[] = $row;
            }
        }

        $collectedNoShow = self::countSpread($filteredData);

        // prepare collected array
        $collected = $collectedNoShow[0];

        // prepare no-show array
        $totalNoShow = $collectedNoShow[1];

        // Construct weekly forecast array
        return array(
            "AvgMondayCollected" => (int)round($collected["Monday"] / $numWeeks),
            "AvgTuesdayCollected" => (int)round($collected["Tuesday"] / $numWeeks),
            "AvgWednesdayCollected" => (int)round($collected["Wednesday"] / $numWeeks),
            "AvgThursdayCollected" => (int)round($collected["Thursday"] / $numWeeks),
            "AvgFridayCollected" => (int)round($collected["Friday"] / $numWeeks),
            "AvgSaturdayCollected" => (int)round($collected["Saturday"] / $numWeeks),
            "AvgSundayCollected" => (int)round($collected["Sunday"] / $numWeeks),
            "AvgMondayNoShow" => (int)round($totalNoShow["Monday"] / $numWeeks),
            "AvgTuesdayNoShow" => (int)round($totalNoShow["Tuesday"] / $numWeeks),
            "AvgWednesdayNoShow" => (int)round($totalNoShow["Wednesday"] / $numWeeks),
            "AvgThursdayNoShow" => (int)round($totalNoShow["Thursday"] / $numWeeks),
            "AvgFridayNoShow" => (int)round($totalNoShow["Friday"] / $numWeeks),
            "AvgSaturdayNoShow" => (int)round($totalNoShow["Saturday"] / $numWeeks),
            "AvgSundayNoShow" => (int)round($totalNoShow["Sunday"] / $numWeeks),
        );
    }

    public static function countSpread($data) : array {
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

        // Sort by collected/no-show and by day
        foreach($data as $row) {
            // Check what day it is and add the stats to that day & increment days
            switch ($row[0]) {
                case "Monday":
                    if ($row[6] == "collected") {
                        $collected["Monday"] += 1;
                    } else {
                        $totalNoShow["Monday"] += 1;
                    }
                    break;
                case "Tuesday":
                    if ($row[6] == "collected") {
                        $collected["Tuesday"] += 1;
                    } else {
                        $totalNoShow["Tuesday"] += 1;
                    }
                    break;
                case "Wednesday":
                    if($row[6] == "collected") {
                        $collected["Wednesday"] += 1;
                    } else {
                        $totalNoShow["Wednesday"] += 1;
                    }
                    break;
                case "Thursday":
                    if ($row[6] == "collected") {
                        $collected["Thursday"] += 1;
                    } else {
                        $totalNoShow["Thursday"] += 1;
                    }
                    break;
                case "Friday":
                    if ($row[6] == "collected") {
                        $collected["Friday"] += 1;
                    } else {
                        $totalNoShow["Friday"] += 1;
                    }
                    break;
                case "Saturday":
                    if ($row[6] == "collected") {
                        $collected["Saturday"] += 1;
                    } else {
                        $totalNoShow["Saturday"] += 1;
                    }
                    break;
                case "Sunday":
                    if ($row[6] == "collected") {
                        $collected["Sunday"] += 1;
                    } else {
                        $totalNoShow["Sunday"] += 1;
                    }
                    break;

            }
        }

        return array($collected, $totalNoShow);
    }
}


