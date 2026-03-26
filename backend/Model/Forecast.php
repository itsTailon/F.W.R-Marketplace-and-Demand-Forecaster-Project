<?php

namespace TTE\App\Model;

use DateInterval;
use DateTime;

//include 'backend/Dataset/forecast.csv';

class Forecast
{
    /*
    public static function getData() : array {
        // Open file and prepare array
        return array_map('str_getcsv', file(__DIR__ . '/../Dataset/testData.csv'));
    }

    public static function getBundleData() : array {
        // Open file and prepare array
        return array_map('str_getcsv', file(__DIR__ . '/../Dataset/bundles.csv'));
    }
    */

    /**
     * calculates the moving average for a set of reservations, only accounting for reservations meeting the specified parameters
     *
     * @param string $startTime
     * @param string $endTime
     * @param int $minDiscount
     * @param int $maxDiscount
     * @param $reservations
     * @return int[]
     */
    public static function movingAverage(string $filterCategory, string $startTime, string $endTime, int $minDiscount, int $maxDiscount,$filterWeatherConditions, $reservations) : array {
        // get all needed data
        $data = Forecast::formatData($reservations);

        // get the number of weeks
        $numWeeks = array_pop($data);

        // Filter out values
        $filteredData = array();
        foreach($data as $row) {
            if(
                // Check if datum needs to be filtered out
                (int)$row[2] >= $minDiscount
                && (int)$row[2] <= $maxDiscount
                && ($filterCategory == 'any' || $row[4] == $filterCategory)
                && ($filterWeatherConditions == 'any' || $row[5] == $filterWeatherConditions)
                && $startTime <= (int)$row[6]
                && $endTime >= (int)$row[6]
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

    /**
     * @throws DatabaseException
     */
    public static function formatData(array $allReservations): array {
        // get all related reservations

        $data = array();
        $numberOfWeeks = 1;
        $lastDay = null;

        foreach($allReservations as $reservation) {
            $day = getdate(strtotime($reservation["reservationDate"]))["weekday"];
            $date = getdate(strtotime($reservation["reservationDate"]));
            $time = getdate(strtotime($reservation["reservationDate"]))["hours"];

            if($lastDay != null) {
                $lastDay_Day = $lastDay['wday'];
                if($lastDay_Day == 0) $lastDay_Day = 7;
                $currentDay_Day = $date['wday'];
                if($currentDay_Day == 0) $currentDay_Day = 7;

                if($lastDay_Day > $currentDay_Day || (($lastDay_Day == $currentDay_Day) && ($lastDay['mday'] != $date['mday'])) || (($lastDay_Day < $currentDay_Day) && (($lastDay['mday'] < $date['mday']) || ($lastDay['mon'] < $date['mon'])))) {
                    $numberOfWeeks++;
                }
            }

            $lastDay = $date;

            $relatedBundle = Bundle::load($reservation["bundleID"]);
            $discountedPrice = $relatedBundle->getDiscountedPriceGBX();
            $rrp = $relatedBundle->getRrpGBX();
            $category = $relatedBundle->getCategory();
            $weather = $reservation["weatherCondition"];
            $pickUp = explode(":", $relatedBundle->getPickupWindow())[0];

            $discountPercentage = ($discountedPrice/$rrp)*100;

            $status = $reservation["reservationStatus"];

            if($status == "completed" || $status == "no-show") {
                $dataPoint =  array($day, $time, $discountPercentage, $status, $category, $weather, $pickUp);
                $data[] = $dataPoint;
            }
        }

        $data[] = $numberOfWeeks;

        return $data;
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
                    if ($row[3] == "completed") {
                        $collected["Monday"] += 1;
                    } elseif ($row[3] == "no-show") {
                        $totalNoShow["Monday"] += 1;
                    }
                    break;
                case "Tuesday":
                    if ($row[3] == "completed") {
                        $collected["Tuesday"] += 1;
                    } elseif ($row[3] == "no-show") {
                        $totalNoShow["Tuesday"] += 1;
                    }
                    break;
                case "Wednesday":
                    if($row[3] == "completed") {
                        $collected["Wednesday"] += 1;
                    } elseif ($row[3] == "no-show") {
                        $totalNoShow["Wednesday"] += 1;
                    }
                    break;
                case "Thursday":
                    if ($row[3] == "completed") {
                        $collected["Thursday"] += 1;
                    } elseif ($row[3] == "no-show") {
                        $totalNoShow["Thursday"] += 1;
                    }
                    break;
                case "Friday":
                    if ($row[3] == "completed") {
                        $collected["Friday"] += 1;
                    } elseif ($row[3] == "no-show") {
                        $totalNoShow["Friday"] += 1;
                    }
                    break;
                case "Saturday":
                    if ($row[3] == "completed") {
                        $collected["Saturday"] += 1;
                    } elseif ($row[3] == "no-show") {
                        $totalNoShow["Saturday"] += 1;
                    }
                    break;
                case "Sunday":
                    if ($row[3] == "completed") {
                        $collected["Sunday"] += 1;
                    } elseif ($row[3] == "no-show") {
                        $totalNoShow["Sunday"] += 1;
                    }
                    break;

            }
        }

        return array($collected, $totalNoShow);
    }

    public static function forecastNextWeekSeasonal($filterCategory, $startTime, $endTime, $minDiscount, $maxDiscount, $filterWeatherCondition, $reservationData) : array {
        $filteredData = array();

        foreach($reservationData as $row) {
            $relatedBundle = Bundle::load($row["bundleID"]);
            $discountedPrice = $relatedBundle->getDiscountedPriceGBX();
            $rrp = $relatedBundle->getRrpGBX();
            $discountPercentage = ($discountedPrice/$rrp)*100;
            $pickUp = explode(":", $relatedBundle->getPickupWindow())[0];
            if(
                $discountPercentage >= $minDiscount
                && $discountPercentage <= $maxDiscount
                && $pickUp >= $startTime
                && $pickUp <= $endTime
            ) {
                $filteredData[] = $row;
            }
        }

        $data = array();

        $daysCollected = array(
            "Monday" => 0,
            "Tuesday" => 0,
            "Wednesday" => 0,
            "Thursday" => 0,
            "Friday" => 0,
            "Saturday" => 0,
            "Sunday" => 0
        );

        $daysNoShow = array(
            "Monday" => 0,
            "Tuesday" => 0,
            "Wednesday" => 0,
            "Thursday" => 0,
            "Friday" => 0,
            "Saturday" => 0,
            "Sunday" => 0
        );

        foreach($filteredData as $reservation) {
            // load relevant data
            $date = getdate(strtotime($reservation["reservationDate"]))["weekday"];

            $status = $reservation["reservationStatus"];

            // update arrays if data aligns
            if ($status == "completed" || $status == "no-show") {

                if($status == "completed") {
                    $daysCollected[$date] += 1;
                }

                if($status == "no-show") {
                    $daysNoShow[$date] += 1;
                }
            }
        }

        $probabilities = Forecast::calculateProbabilitySpread(-1);


        $finalProb = 1;

        if($filterCategory != "any") {
            $finalProb = $finalProb * ($probabilities['category'][$filterCategory]);
        }

        if($filterWeatherCondition != "any") {
            $finalProb = $finalProb * ($probabilities['weatherCondition'][$filterWeatherCondition]);
        }

        // calculate : predicted bundle requirements (for the specified bundle) for each week and probability the bundle is collected each day (for the specified bundle)
        $forecastedData = array(
            'neededBundlesMonday' => $daysCollected['Monday'] * $finalProb,
            'neededBundlesTuesday' => $daysCollected['Tuesday'] * $finalProb,
            'neededBundleWednesday' => $daysCollected['Wednesday'] * $finalProb,
            'neededBundleThursday' => $daysCollected['Thursday'] * $finalProb,
            'neededBundleFriday' => $daysCollected['Friday'] * $finalProb,
            'neededBundleSaturday' => $daysCollected['Saturday'] * $finalProb,
            'neededBundleSunday' => $daysCollected['Sunday'] * $finalProb,

            'probabilityCollectedMonday' => $probabilities['date']['Monday'],
            'probabilityCollectedTuesday' => $probabilities['date']['Tuesday'],
            'probabilityCollectedWednesday' => $probabilities['date']['Thursday'],
            'probabilityCollectedFriday' => $probabilities['date']['Friday'],
            'probabilityCollectedSaturday' => $probabilities['date']['Saturday'],
            'probabilityCollectedSunday' => $probabilities['date']['Sunday'],
        );

        return $forecastedData;
    }

    /**
     * @throws \DateInvalidOperationException
     * @throws DatabaseException
     */
    public static function getLastWeeksReservations($id): array
    {
        // prepare needed dates
        $today = new \DateTime();

        if (getdate($today->getTimestamp())['wday'] == 0) {
            $lastWeekStart = $today->sub(DateInterval::createFromDateString('+13 days'));
            $lastWeekStartClone = clone $lastWeekStart;
            $lastWeekEnd = $lastWeekStartClone->sub(DateInterval::createFromDateString('-6 days'));
        } else {
            $lastWeekStart = $today->sub(DateInterval::createFromDateString('+' . (getdate($today->getTimestamp())['wday'] + 6) . ' days'));
            $lastWeekStartClone = clone $lastWeekStart;
            $lastWeekEnd = $lastWeekStartClone->sub(DateInterval::createFromDateString('-6 days'));
        }

        $lastWeekStart = date_format($lastWeekStart, 'Y/m/d H:i:s');
        $lastWeekEnd = date_format($lastWeekEnd, 'Y/m/d H:i:s');

        $reservations = Reservation::getAllReservationsForUser($id, 'seller');
        $lastWeekReservations = array();

        // if the reservation falls withing the given week, add it the array
        foreach($reservations as $reservation) {
            if(
                (getdate(strtotime($reservation['reservationDate']))['year'] == getdate(strtotime($lastWeekStart))['year'] || getdate(strtotime($reservation['reservationDate']))['year'] == getdate(strtotime($lastWeekEnd))['year'])
                && (getdate(strtotime($reservation['reservationDate']))['mon'] == getdate(strtotime($lastWeekStart))['mon'] || getdate(strtotime($reservation['reservationDate']))['mon'] == getdate(strtotime($lastWeekEnd))['mon'])
                && (getdate(strtotime($reservation['reservationDate']))['yday'] >= getdate(strtotime($lastWeekStart))['yday'] && getdate(strtotime($reservation['reservationDate']))['yday'] <= getdate(strtotime($lastWeekEnd))['yday'])
            ) {
                $lastWeekReservations[] = $reservation;
            }
        }

        return $lastWeekReservations;
    }

    /**
     * @throws DatabaseException
     */
    public static function calculateProbabilitySpread(int $id = -1): array {
        //get data
        if($id == -1) {
            $allReservations = self::getAllReservations();
        } else {
            $allReservations = Reservation::getAllReservationsForUser($id, 'seller');
        }

        $data = array();

        // all possible categories
        $probabilities = array(
            'date' => array(),
            'category' => array(),
            'time' => array(),
            'discountPercentage' => array(),
            'weatherCondition' => array()
        );

        $dateNoShow = array();
        $categoryNoShow = array();
        $discountNoShow = array();
        $weatherConditionNoShow = array();
        $timeNoShow = array();

        $dateCollected = array();
        $categoryCollected = array();
        $discountCollected = array();
        $weatherConditionCollected = array();
        $timeCollected = array();


        $dates = array(
            "Monday",
            "Tuesday",
            "Wednesday",
            "Thursday",
            "Friday",
            "Saturday",
            "Sunday"
        );
        $categories = array();
        $times = array();
        $discounts = array();
        $weatherConditions = array();

        foreach($allReservations as $reservation) {
            // get the date and time
            $date = getdate(strtotime($reservation["reservationDate"]))["weekday"];


            // get the related bundle
            $relatedBundle = Bundle::load($reservation["bundleID"]);

            // get needed information
            $discountedPrice = $relatedBundle->getDiscountedPriceGBX();
            $rrp = $relatedBundle->getRrpGBX();
            $discountPercentage = ($discountedPrice/$rrp)*100;

            $category = $relatedBundle->getCategory();

            $weatherCondition = $reservation["weatherCondition"];

            $status = $reservation["reservationStatus"];

            $time = explode(":", $relatedBundle->getPickupWindow())[0];

            // only account for completed bundles
            if($status == "completed" || $status == "no-show") {
                // if the category specification has not been seen before
                if(!in_array($category, $categories)) {
                    $categories[] = $category;
                }

                if(!in_array($discountPercentage, $discounts)) {
                    $discounts[] = $discountPercentage;
                }

                if(!in_array($weatherCondition, $weatherConditions)) {
                    $weatherConditions[] = $weatherCondition;
                }

                if(!in_array($time, $times)) {
                    $times[] = $time;
                }
            }

            // add 1 to relevant specifications
            if($status == "no-show") {
                if (!isset($dateNoShow[$date])) {
                    $dateNoShow[$date] = 0;
                }

                if (!isset($categoryNoShow[$category])) {
                    $categoryNoShow[$category] = 0;
                }

                if (!isset($discountNoShow[$discountPercentage])) {
                    $discountNoShow[$discountPercentage] = 0;
                }

                if (!isset($weatherConditionNoShow[$weatherCondition])) {
                    $weatherConditionNoShow[$weatherCondition] = 0;
                }

                if (!isset($timeNoShow[$time])) {
                    $timeNoShow[$time] = 0;
                }

                $dateNoShow[$date] += 1;
                $categoryNoShow[$category] += 1;
                $discountNoShow[$discountPercentage] += 1;
                $weatherConditionNoShow[$weatherCondition] += 1;
                $timeNoShow[$time] += 1;
            } elseif ($status == "completed") {
                if (!isset($dateCollected[$date])) {
                    $dateCollected[$date] = 0;
                }

                if (!isset($categoryCollected[$category])) {
                    $categoryCollected[$category] = 0;
                }

                if (!isset($discountCollected[$discountPercentage])) {
                    $discountCollected[$discountPercentage] = 0;
                }

                if (!isset($weatherConditionCollected[$weatherCondition])) {
                    $weatherConditionCollected[$weatherCondition] = 0;
                }

                if (!isset($timeCollected[$time])) {
                    $timeCollected[$time] = 0;
                }

                $dateCollected[$date] += 1;
                $categoryCollected[$category] += 1;
                $discountCollected[$discountPercentage] += 1;
                $weatherConditionCollected[$weatherCondition] += 1;
                $timeCollected[$time] += 1;
            }
        }

        // calculate probability for each of the specifications
        $probabilities['date'] = Forecast::calculateProbability($dateCollected,$dateNoShow,$dates);
        $probabilities['category'] = Forecast::calculateProbability($categoryCollected,$categoryNoShow,$categories);
        $probabilities['time'] = Forecast::calculateProbability($timeCollected,$timeNoShow,$times);
        $probabilities['discountPercentage'] = Forecast::calculateProbability($discountCollected,$discountNoShow,$discounts);
        $probabilities['weatherCondition'] = Forecast::calculateProbability($weatherConditionCollected,$weatherConditionNoShow,$weatherConditions);

        return $probabilities;
    }

    public static function calculateProbability(array $collected, array $noShow, array $keys) : array {
        $probabilityArray = array();

        // calculates a probability of collection based off a list of collected information and no-show information for a given specification
        foreach ($keys as $key) {
            if (!isset($collected[$key])) { // no listings
                $probabilityArray[strval($key)] = 0;
            } else if (!isset($noShow[$key])){ // all listings have been collected
                $probabilityArray[strval($key)] = 1;
            } else { // calculate probability
                $probabilityArray[strval($key)] = $collected[$key] / ($noShow[$key] + $collected[$key]);
            }
        }

        return $probabilityArray;
    }

    /*
    public function filterByCategory(string $category, string $specification, array $data): array {
        $filteredData = array();

        foreach ($data as $row) {
            if ($row[$category] == $specification) {
                $filteredData[] = $row;
            }
        }

        return $filteredData;
    }
    */

    /**
     * @throws DatabaseException
     */
    public static function getAllReservations(): array {
        // prepare query
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM reservation");

        // Attempt to execute the statement
        try{
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e){
            throw new DatabaseException($e->getMessage());
        }
    }

    public static function compareWithGroundTruth($id, $method){
        // initialise values
        $allReservations = Reservation::getAllReservationsForUser($id, 'seller');

        $trueProduction = array();
        $predictedProduction = array();

        $firstDay = true;
        $currentWeekTotal = 0;
        $currentReservations = array();

        // iterate through all given reservations
        foreach($allReservations as $reservation) {
            // calculate production for each day until sunday, forecast next week, then move onto the next week
            $weekDay = getdate(strtotime($reservation["reservationDate"]));

            $currentReservations[] = $reservation;

            if (
                !$firstDay
                && (
                    ($weekDay['wday'] != 0 && $weekDay['wday'] < $lastDay['wday'])
                    || ($lastDay['wday'] == 0 && $lastDay['wday'] != $weekDay['wday'])
                    || ($lastDay['wday'] == 0 && $weekDay['wday'] == 0 && $weekDay['yday'] != $lastDay['yday'])
                    || ($lastDay['wday'] == $weekDay['wday'] && $weekDay['yday'] != $lastDay['yday'])
                    || ($lastDay['wday'] < $weekDay['wday'] && $weekDay['yday'] > ($lastDay['yday'] + (8 - $lastDay['wday'])))
                )
            ) {
                $trueProduction[] = $currentWeekTotal;
                $totalPrediction = 0;
                if ($method == "MovingAverage") {
                    $forecastedForNextWeek = Forecast::movingAverage("any", 0, 24, 0, 100, "any", $currentReservations);
                    foreach ($forecastedForNextWeek as $predictedValue){
                        $totalPrediction += $predictedValue;
                    }
                } elseif ($method == "Seasonal") {
                    $forecastedForNextWeek = Forecast::forecastNextWeekSeasonal("any", 0, 24, 0, 100, "any", $currentReservations);
                    $currentReservations = array();
                    $count = 0;
                    while ($count < 7) {
                        $totalPrediction += $forecastedForNextWeek[$count];
                        $count++;
                    }
                }

                $predictedProduction[] = $totalPrediction;
                $currentWeekTotal = 0;
            } else {
                $firstDay = false;
            }
            $currentWeekTotal += 1;
            $lastDay = $weekDay;
        }

        // true production over time / predicted production over time
        return array($trueProduction, $predictedProduction);
    }

    /**
     * @throws DatabaseException
     */
    public static function getProductionRecommendation(Bundle $bundle): array {
        // get the value list for average listings for this bundle
        $category = $bundle->getCategory();
        $reservations = Reservation::getAllReservationsForUser($bundle->getSellerId(), "seller");
        $movingAvg = self::movingAverage($category, 0, 24, 0, 100, 'any', $reservations);

        // get probability list
        $probabilities = self::calculateProbabilitySpread();

        // get the average listings for bundles
        $count = 0;
        $collected = 0;
        $noShow = 0;
        foreach ($movingAvg as $movingAvgValue) {
            if($count <= 7){
                $collected += $movingAvgValue;
            } else {
                $noShow += $movingAvgValue;
            }

            $count++;
        }

        $quantity = $collected - $bundle->getQuantity();

        // find the best listing time
        $highestProb = 0;
        $count = 0;
        $bestTime = 0;
        while ($count <= 24) {
            if(isset($probabilities['time'][strval($count)]) && $probabilities['time'][strval($count)] > $highestProb){
                $highestProb = $probabilities['time'][strval($count)];
                $bestTime = $count;
            }
            $count++;
        }

        if($bestTime != 0) {
            $timeFormat = strval($bestTime) . ":00-" . strval($bestTime + 1) . ":00";
        } else {
            $bestTime = "unavailable (not enough data)";
        }

        // return array of data
        return array($collected, $noShow, $quantity, $timeFormat);
    }
}

