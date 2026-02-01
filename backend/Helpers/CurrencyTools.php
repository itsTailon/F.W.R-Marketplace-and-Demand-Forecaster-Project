<?php
namespace TTE\App\Helpers;

class CurrencyTools {
    private function __construct() {}

    /**
     * Converts a string representation of a (MySQL) DECIMAL type to an integer representing the total number of pence (GBX).
     *
     * Example: "2.50" => 250
     *
     * @param string $decimal
     * @throws \ValueError if $decimal is not of valid format
     * @return int amount represented as total number of pence
     */
    public static function decimalStringToGBX(string $decimal): int {
        // Split input into LHS and RHS
        $split = explode('.', $decimal);

        // Ensure that LHS and RHS are purely numeric
        if (!is_numeric($split[0]) || !is_numeric($split[1])) {
            throw new \ValueError("Invalid number '$decimal'");
        }

        $lhs = intval($split[0]);
        $rhs = intval($split[1]);

        // Convert to pence (GBX)
        return ($lhs * 100) + $rhs;
    }

    /**
     * Converts an integer representing the total number of pence (GBX) to a string representation of a (MySQL) DECIMAL type.
     *
     * Example: "250" => 2.50
     *
     * @param int $gbx
     * @throws \ValueError if $gbx is not of valid format
     * @return string amount represented as total pounds
     */
    public static function gbxToDecimalString(int $gbx) : string {
        //Check valid input
        if ($gbx < 0) {
            throw new \ValueError("Invalid number '$gbx'");
        }

        // Get pence for decimal value
        $pence = $gbx % 100;
        $penceStr = "";

        if ($pence < 10) {
            // Pad pence and convert to string
            $penceStr = "0$pence";
        } else {
            $penceStr = "$pence";
        }

        // Get pounds from integer values
        // TODO: check behaviour of string cast (perhaps use conv. function)
        $poundsStr = (string)intdiv($gbx, 100);

        // Creating string forming decimal value
        return "$poundsStr.$penceStr";
    }

}