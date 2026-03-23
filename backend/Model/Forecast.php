<?php

namespace TTE\App\Model;

use DateInterval;
use DateTime;

include '../Dataset/forecast.csv';

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
                (int)$row[1] >= $startTime
                && (int)$row[1] <= $endTime
                && (int)$row[2] >= $minDiscount
                && (int)$row[2] <= $maxDiscount
                && ($filterCategory == 'any' || $row[4] == $filterCategory)
                && ($filterWeatherConditions == 'any' || $row[4] == $filterWeatherConditions)
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

    public static function formatData(array $allReservations): array {
        // get all related reservations

        $data = array();
        $numberOfWeeks = 1;
        $lastDay = null;

        foreach($allReservations as $reservation) {
            $day = getdate(strtotime($reservation["reservationDate"]))["weekday"];
            $date = getdate(strtotime($reservation["reservationDate"]));
            $time = getdate(strtotime($reservation["reservationTime"]))["hours"];

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

            $discountPercentage = ($discountedPrice/$rrp)*100;

            $status = $reservation["reservationStatus"];

            if($status == "completed" || $status == "no-show") {
                $dataPoint =  array($day, $time, $discountPercentage, $status, $category, $weather);
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

        foreach($reservationData as $reservation) {
            // load relevant data
            $date = getdate($reservation["reservationDate"])["weekday"];
            $time = getdate($reservation["reservationTime"])["hours"];

            $relatedBundle = Bundle::load($reservation["bundleID"]);

            $discountedPrice = $relatedBundle->getDiscountedPriceGBX();
            $rrp = $relatedBundle->getRrpGBX();
            $discountPercentage = ($discountedPrice / $rrp) * 100;

            $category = $relatedBundle->getCategory();

            $weatherCondition = $reservation["weatherCondition"];

            $status = $reservation["reservationStatus"];

            // update arrays if data aligns
            if ($status == "collected" || $status == "no-show") {
                $dataPoint = array($date, $time, $discountPercentage, $category, $weatherCondition, $status);
                $data[] = $dataPoint;

                if($status == "collected") {
                    $daysCollected[$date] += 1;
                }

                if($status == "no-show") {
                    $daysNoShow[$date] += 1;
                }
            }
        }

        $probabilities = Forecast::calculateProbabilitySpread(-1);

        $collectedNoShow = self::countSpread($filteredData);

        // prepare collected array
        $collected = $collectedNoShow[0];

        // prepare no-show array
        $totalNoShow = $collectedNoShow[1];

        $finalProb = 1;

        if($filterCategory != "any") {
            $finalProb = $finalProb * ($probabilities['category'][$filterCategory]);
        }

        // Calculate discount probability
        $currentDiscount = $minDiscount;
        $discountProbability = 0;
        while($currentDiscount <= $maxDiscount) {
            $discountProbability += $probabilities['discount'][$currentDiscount];
            $currentDiscount++;
        }
        $finalProb = $finalProb * $discountProbability;


        if($filterWeatherCondition != "any") {
            $finalProb = $finalProb * ($probabilities['weatherCondition'][$filterWeatherCondition]);
        }

        if($daysNoShow['Monday'] + $daysNoShow['Monday'] == 0) {
            $probCollectedMonday = 1;
        } else {
            $probCollectedMonday = $daysCollected['Monday'] / ($daysNoShow['Monday'] + $daysNoShow['Monday']);
        }

        if($daysNoShow['Tuesday'] + $daysNoShow['Tuesday'] == 0) {
            $probCollectedTuesday = 1;
        } else {
            $probCollectedTuesday = $daysCollected['Tuesday'] / ($daysNoShow['Tuesday'] + $daysNoShow['Tuesday']);
        }

        if($daysNoShow['Wednesday'] + $daysNoShow['Wednesday'] == 0) {
            $probCollectedWednesday = 1;
        } else {
            $probCollectedWednesday = $daysCollected['Wednesday'] / ($daysNoShow['Wednesday'] + $daysNoShow['Wednesday']);
        }

        if($daysNoShow['Thursday'] + $daysNoShow['Thursday'] == 0) {
            $probCollectedThursday = 1;
        } else {
            $probCollectedThursday = $daysCollected['Thursday'] / ($daysNoShow['Thursday'] + $daysNoShow['Thursday']);
        }

        if($daysNoShow['Friday'] + $daysNoShow['Friday'] == 0) {
            $probCollectedFriday = 1;
        } else {
            $probCollectedFriday = $daysCollected['Friday'] / ($daysNoShow['Friday'] + $daysNoShow['Friday']);
        }

        if($daysNoShow['Saturday'] + $daysNoShow['Saturday'] == 0) {
            $probCollectedSaturday = 1;
        } else {
            $probCollectedSaturday = $daysCollected['Saturday'] / ($daysNoShow['Saturday'] + $daysNoShow['Saturday']);
        }

        if($daysNoShow['Sunday'] + $daysNoShow['Sunday'] == 0) {
            $probCollectedSunday = 1;
        } else {
            $probCollectedSunday = $daysCollected['Sunday'] / ($daysNoShow['Sunday'] + $daysNoShow['Sunday']);
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

            'probabilityCollectedMonday' => $probCollectedMonday,
            'probabilityCollectedTuesday' => $probCollectedTuesday,
            'probabilityCollectedWednesday' => $probCollectedWednesday,
            'probabilityCollectedThursday' => $probCollectedThursday,
            'probabilityCollectedFriday' => $probCollectedFriday,
            'probabilityCollectedSaturday' => $probCollectedSaturday,
            'probabilityCollectedSunday' => $probCollectedSunday
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
            if(strtotime($reservation['reservationDate']) >= strtotime($lastWeekStart) && strtotime($reservation['reservationDate']) <= strtotime($lastWeekEnd)) {
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
        $timeNoShow = array();
        $discountNoShow = array();
        $weatherConditionNoShow = array();

        $dateCollected = array();
        $categoryCollected = array();
        $timeCollected = array();
        $discountCollected = array();
        $weatherConditionCollected = array();

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
            $time = getdate(strtotime($reservation["reservationDate"]))["hours"];

            // get the related bundle
            $relatedBundle = Bundle::load($reservation["bundleID"]);

            // get needed information
            $discountedPrice = $relatedBundle->getDiscountedPriceGBX();
            $rrp = $relatedBundle->getRrpGBX();
            $discountPercentage = ($discountedPrice/$rrp)*100;

            $category = $relatedBundle->getCategory();

            $weatherCondition = $reservation["weatherCondition"];

            $status = $reservation["reservationStatus"];

            // only account for completed bundles
            if($status == "collected" || $status == "no-show") {
                $dataPoint =  array($date, $time, $discountPercentage,$category,$weatherCondition, $status);
                $data[] = $dataPoint;

                // if the category specification has not been seen before
                if(!in_array($category, $categories)) {
                    $categories[] = $category;
                }

                if(!in_array($time, $times)) {
                    $times[] = $time;
                }

                if(!in_array($discountPercentage, $discounts)) {
                    $discounts[] = $discountPercentage;
                }

                if(!in_array($weatherCondition, $weatherConditions)) {
                    $weatherConditions[] = $weatherCondition;
                }
            }

            // add 1 to relevant specifications
            if($status == "no-show") {
                $dateNoShow[$date] += 1;
                $categoryNoShow[$category] += 1;
                $timeNoShow[$time] = +1;
                $discountNoShow[$discountPercentage] = 1;
                $weatherConditionNoShow[$weatherCondition] = 1;
            } elseif ($status == "collected") {
                $dateCollected[$date] += 1;
                $categoryCollected[$category] += 1;
                $timeCollected[$time] = +1;
                $discountCollected[$discountPercentage] = 1;
                $weatherConditionCollected[$weatherCondition] = 1;
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
            if ($collected[$key] == null){ // no listings
                $probabilityArray[$key] = 0;
            } else if ($noShow[$key] == null){ // all listings have been collected
                $probabilityArray[$key] = 1;
            } else { // calculate probability
                $probabilityArray[$key] = $collected[$key] / $noShow[$key] + $collected[$key];
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

        $firstWeek = true;
        $endOfWeek = false;
        $currentWeekTotal = 0;
        $currentReservations = array();


        // iterate through all given reservations
        foreach($allReservations as $reservation) {
            // calculate production for each day until sunday, forecast next week, then move onto the next week
            $weekDay = getdate($reservation["reservationDate"])["weekday"];

            if(!($method == "Seasonal" && $reservation["status"] == "no-show")) {
                $currentReservations[] = $reservation;
            }

            if ($weekDay == 'Monday' && !$firstWeek && $endOfWeek) {
                $trueProduction[] = $currentWeekTotal;
                if ($method == "MovingAverage") {
                    $forecastedForNextWeek = Forecast::movingAverage("00:00", "24:00", "0", "100", $currentReservations);
                } elseif ($method == "Seasonal") {
                    $forecastedForNextWeek = Forecast::forecastNextWeekSeasonal($id, "any", 0, 24, -1, "any", "date", $currentReservations);
                }
                $totalPrediction = 0;
                foreach ($forecastedForNextWeek as $predictedValue){
                    $totalPrediction += $predictedValue;
                }
                $predictedProduction[] = $totalPrediction;
                $currentWeekTotal = 0;
                $endOfWeek = false;
            } elseif (!$firstWeek) {
                $currentWeekTotal += 1;
            } elseif(!$firstWeek && $weekDay == 'Sunday') {
                $endOfWeek = true;
            }elseif($firstWeek && $weekDay == 'Sunday'){
                $firstWeek = false;
                $endOfWeek = true;
            }
        }

        // true production over time / predicted production over time
        return array($trueProduction, $predictedProduction);
    }

    public function getProductionRecommendation(Bundle $bundle): array {
        // get the value list for average listings for this bundle
        $movingAvg = Self::getMovingAvg($bundle->getPickupWindow(), $bundle->getPickupWindow(), 0, 100, Reservation::getAllReservationsForUser($bundle->getSellerId(), "seller"));

        // get probability list
        $probabilities = Self::calculateProbabilitySpread();

        // get the average listings for bundles
        $count = 0;
        $collected = 0;
        $noShow = 0;
        foreach ($movingAvg as $movingAvgValue) {
            if($count == 7){
                $collected += $movingAvgValue;
            } else {
                $noShow += $movingAvgValue;
            }

            $count++;
        }
        $collected = intdiv($collected, 7);
        $noShow = intdiv($noShow, 7);

        $quantity = $collected - $bundle->getQuantity();

        // find the best listing time
        $highestProb = 0;
        $highestTime = 0;
        $count = 0;
        while ($count <= 24) {
            if($probabilities['time'][strval($count)] > $highestProb){
                $highestProb = $probabilities['time'][strval($count)];
                $highestTime = $count;
            }
        }

        // return array of data
        return array($collected, $noShow, $quantity, $highestProb);
    }
}


