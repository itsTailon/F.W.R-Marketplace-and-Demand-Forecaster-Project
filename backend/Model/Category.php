<?php

namespace TTE\App\Model;

use TTE\App\Model\MissingValuesException;
use TTE\App\Model\CategoryAlreadyExistsException;
use TTE\App\Model\NoSuchCategoryException;
use TTE\App\Model\DatabaseException;


class Category {

    /**
     * Returns true if an bundle exists with the given name.
     *
     * @param string $categoryName
     * @return bool
     */
    public static function categoryExists(string $categoryName): bool {
        // Prepare parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM category WHERE categoryName=:categoryName;");

        // Execute statement with given category name
        $stmt->execute([":categoryName" => $categoryName]);

        // Get result
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Return true if an category exists with the given name
        return !($row === false);
    }

    /**
     * Returns an array containing the names of all possible categories (i.e. all categories stored in the database).
     *
     * In the case of a DB query failure, an empty array is returned.
     *
     * @return array Array of strings representing the names of all categories.
     */
    public static function getCategoryList(): array {
        $stmt = DatabaseHandler::getPDO()->query("SELECT categoryName FROM category;");

        // If the query fails, gracefully return an empty array.
        if ($stmt === false) {
            return [];
        }

        // Return (indexed, not assoc.) array of category names
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Allows maintainer to add a new category to the DB
     *
     * @throws CategoryAlreadyExistsException|MissingValuesException|DatabaseException
     * @return void No output as should never each a case where (if bool was used) it hadn't either been true or thrown an exception
     */
    public static function create(string $categoryName): void {

        // Check category entered is a string and has some text
        if (empty(trim($categoryName))) {
            // throw error as missing parameter required
            throw new MissingValuesException("Missing category parameter");
        }

        // Check that category doesn't already exist
        if (Category::categoryExists($categoryName)) {
            throw new CategoryAlreadyExistsException("Category $categoryName already exists");
        }

        try {
            // Otherwise, create new category
            $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO category (categoryName) VALUES (:categoryName);");
            $stmt->execute([":categoryName" => $categoryName]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

    }

    /**
     * Deletion method for a category within the database
     *
     * @throws DatabaseException|NoSuchCategoryException
     */
    public static function delete(string $categoryName): void {
        // Create SQL command to delete category of given name
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM category WHERE categoryName=:categoryName;");

        // Check if category exists
        if (Category::categoryExists($categoryName)) {
            // Attempt to run SQL statement
            try {
                $stmt->execute(["bundleID" => $categoryName]);
            } catch (\PDOException $e) {
                throw new DatabaseException($e->getMessage());
            }
        } else {
            // If category does not exist, throw error
            throw new NoSuchCategoryException("No $categoryName category was found");
        }
    }
}