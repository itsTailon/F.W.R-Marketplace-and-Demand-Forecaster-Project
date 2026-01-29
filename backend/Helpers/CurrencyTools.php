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

    // TODO: Implement inverse of decimalStringToGBX (i.e. pence integer to DECIMAL string)

}