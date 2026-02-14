<?php

namespace TTE\App\Model;


class Allergen {

    /**
     * Returns true if an allergen exists with the given name.
     *
     * @param string $allergenName
     * @return bool
     */
    public static function allergenExists(string $allergenName): bool {
        // Prepare parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM allergen WHERE allergenName=:allergenName;");

        // Execute statement with given allergen name
        $stmt->execute(["allergenName" => $allergenName]);

        // Get result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Return true if an allergen exists with the given name
        return !($row === false);
    }

    /**
     * Returns an array containing the names of all possible allergens (i.e. all allergens stored in the database).
     *
     * In the case of a DB query failure, an empty array is returned.
     *
     * @return array Array of strings representing the names of all allergens.
     */
    public static function getAllergensList(): array {
        $stmt = DatabaseHandler::getPDO()->query("SELECT allergenName FROM allergen;");

        // If the query fails, gracefully return an empty array.
        if ($stmt === false) {
            return [];
        }

        // Return (indexed, not assoc.) array of allergen names
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}