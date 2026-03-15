<?php

namespace TTE\App\Model;

use PDO;
use PDOException;
use TTE\App\Model\StoredObject;
use TTE\App\Model\BadgeTier;

// Class for handling customer badges
class Badge extends StoredObject
{

    // Class attributes
    private int $id;
    private string $title;
    const MAX_LEN_TITLE = 128;
    private string $iconURL;
    private string $subtitle;
    private string $badgeDescription;
    private int $xBronze;
    private int $xSilver;
    private int $xGold;


    /**
     * Updating held values for Badge
     * @return void
     * @throws DatabaseException|MissingValuesException
     */
    public function update(): void
    {
        // Check badge exists
        if (!isset($this->title) || !isset($this->iconURL) || !isset($this->subtitle) ||
            !isset($this->badgeDescription) || !isset($this->xBronze) || !isset($this->xSilver)
            || !isset($this->xGold) || empty(trim($this->title)) || empty(trim($this->iconURL)) ||
            empty(trim($this->subtitle)) || empty(trim($this->badgeDescription))) {
            // Throw error
            throw new MissingValuesException("Missing required information to create a badge.");
        }

        // SQL parameterised query
        $stmt = DatabaseHandler::getPDO()->prepare("UPDATE badge SET title= :title, iconURL = :iconURL, 
        subtitle = :subtitle, badgeDescription = :badgeDescription, xBronze = :xBronze, 
        xSilver = :xSilver, xGold = :xGold;");

        try {
            $stmt->execute([":title" => $this->getTitle(), ":iconURL" => $this->getIconURL(), ":subtitle" => $this->getSubtitle(),
                ":badgeDescription" => $this->getBadgeDescription(), ":xBronze" => $this->getXBronze(),
                ":xSilver" => $this->getXSilver(), ":xGold" => $this->getXGold()]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Method that creates a new Badge object
     * @param array $fields required
     * @return Badge the newly formed badge object
     * @throws DatabaseException|MissingValuesException|BadgeAlreadyExistsException
     */
    public static function create(array $fields): Badge
    {

        // Checking that required fields are passed
        if (!isset($fields['title']) || !isset($fields['iconURL']) || !isset($fields['subtitle']) ||
            !isset($fields['badgeDescription']) || !isset($fields['xBronze']) || !isset($fields['xSilver'])
            || !isset($fields['xGold']) || empty(trim($fields['title'])) || empty(trim($fields['iconURL'])) ||
            empty(trim($fields['subtitle'])) || empty(trim($fields['badgeDescription']))) {
            // Throw error
            throw new MissingValuesException("Missing required information to create a badge.");
        }

        // Create new badge object
        $badge = new Badge();
        $badge->setTitle($fields['title']);
        $badge->setIconURL($fields['iconURL']);
        $badge->setSubtitle($fields['subtitle']);
        $badge->setBadgeDescription($fields['badgeDescription']);
        $badge->setXBronze(intval($fields['xBronze']));
        $badge->setXSilver(intval($fields['xSilver']));
        $badge->setXGold(intval($fields['xGold']));

        // Check if badge with given title already exists
        if (Badge::existsWithIDByTitle($badge->getTitle())) {
            throw new BadgeAlreadyExistsException("Badge with such title already exists.");
        }


        // Create parameterised SQL command
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO badge (title, iconURL, subtitle, badgeDescription, xBronze, xSilver, xGold)
            VALUES (:title, :iconURL, :subtitle, :badgeDescription, :xBronze, :xSilver, :xGold);");

        // Attempt execution of SQL command, throwing database error if failed
        try {
            $stmt->execute([":title" => $badge->getTitle(), ":iconURL" => $badge->getIconURL(), ":subtitle" =>
                $badge->getSubtitle(), ":badgeDescription" => $badge->getBadgeDescription(), ":xBronze" => $badge->getXBronze(),
                ":xSilver" => $badge->getXSilver(), ":xGold" => $badge->getXGold()]);
        } catch (\PDOException $e) {
            // Throw message received by database
            throw new DatabaseException($e->getMessage());
        }

        // Get ID applied to badge as identifier
        $lastId = DatabaseHandler::getPDO()->lastInsertId();
        // Add to object
        $badge->id = intval($lastId);

        // Return formed badge object
        return $badge;

    }

    /**
     * Method loading a Badge object representing the one holding given title
     * @param string $title
     * @return Badge
     * @throws DatabaseException|NoSuchBadgeException
     */
    public static function loadByTitle(string $title): Badge
    {
        // SQL statement formed and executed
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM badge WHERE title=:title;");
        try {
            $stmt->execute([":title" => $title]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Get results
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Ensure badge does exist
        if ($row == false) {
            // Throw error given that badge doesn't exist
            throw new NoSuchBadgeException(("No badge with $title title found."));
        }

        // Construct badge object and perform required data type conversions
        $badge = new Badge;
        $badge->id = intval($row["badgeID"]);
        $badge->setTitle($row['title']);
        $badge->setIconURL($row['iconURL']);
        $badge->SetSubtitle($row['subtitle']);
        $badge->SetBadgeDescription($row['badgeDescription']);
        $badge->setXBronze(intval($row['xBronze']));
        $badge->setXSilver(intval($row['xSilver']));
        $badge->setXGold(intval($row['xGold']));

        return $badge;
    }

    /**
     * Method loading a Badge object representing the one holding given id value
     * @param int $id
     * @return Badge
     * @throws DatabaseException|NoSuchBadgeException
     */
    public static function load(int $id): Badge
    {
        // SQL statement formed and executed
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM badge WHERE badgeID=:id;");
        try {
            $stmt->execute([":id" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Get results
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Ensure badge does exist
        if ($row === false) {
            // Throw error given that badge doesn't exist
            throw new NoSuchBadgeException(("No badge with badge ID of $id"));
        }

        // Construct badge object and perform required data type conversions
        $badge = new Badge;
        $badge->id = intval($row["badgeID"]);
        $badge->setTitle($row['title']);
        $badge->setIconURL($row['iconURL']);
        $badge->SetSubtitle($row['subtitle']);
        $badge->SetBadgeDescription($row['badgeDescription']);
        $badge->setXBronze(intval($row['xBronze']));
        $badge->setXSilver(intval($row['xSilver']));
        $badge->setXGold(intval($row['xGold']));

        return $badge;
    }

    /**
     * Check badge with badge ID passed exists or not
     * @param int $id of badge who's existence is to be verified
     * @return bool return true if does exist false if not
     * @throws DatabaseException
     */
    public static function existsWithID(int $id): bool
    {
        // SQL parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM badge WHERE badgeID=:id;");

        try {
            // Execute statement with given badge ID
            $stmt->execute([":id" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Get result and return boolean value depending
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return !($row === false);
    }

    /**
     * Check badge with badge title passed exists or not
     * @param string $title of badge who's existence is to be verified
     * @return bool return true if does exist false if not
     * @throws DatabaseException
     */
    public static function existsWithIDByTitle(string $title): bool
    {
        // SQL parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM badge WHERE title=:title;");

        try {
            // Execute statement with given badge ID
            $stmt->execute([":title" => $title]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // Get result and return boolean value depending
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return !($row === false);
    }

    /**
     * Delete badge of passed ID
     * @throws NoSuchBadgeException|DatabaseException
     */
    public static function delete(int $id): void {
        // Check if badge exists
        if (!Badge::existsWithID($id)) {
            // Throw exception
            throw new NoSuchBadgeException("No badge with ID $id");
        }
        // SQL statement defined and executed
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM badge WHERE badgeID=:id;");

        try {
            $stmt->execute([":id" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Delete badge of passed title
     * @param string $title
     * @throws NoSuchBadgeException|DatabaseException
     */
    public static function deleteByTitle(string $title): void {
        // Check if badge exists
        if (!Badge::existsWithIDByTitle($title)) {
            // Throw exception
            throw new NoSuchBadgeException("No badge with title $title");
        }
        // SQL statement defined and executed
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM badge WHERE title=:title;");

        try {
            $stmt->execute([":title" => $title]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    // Required getters
    public function getId(): int {
        return $this->id;
    }
    public function getTitle(): string {
        return $this->title;
    }
    public function getIconURL(): string {
        return $this->iconURL;
    }
    public function getSubtitle(): string {
        return $this->subtitle;
    }
    public function getBadgeDescription(): string {
        return $this->badgeDescription;
    }
    public function getXBronze(): int {
        return $this->xBronze;
    }
    public function getXSilver(): int {
        return $this->xSilver;
    }
    public function getXGold(): int {
        return $this->xGold;
    }

    // Required setters
    public function setTitle(string $title): void {
        $this->title = $title;
    }
    public function setIconURL(string $iconURL): void {
        $this->iconURL = $iconURL;
    }
    public function setSubtitle(string $subtitle): void {
        $this->subtitle = $subtitle;
    }
    public function setBadgeDescription(string $badgeDescription): void {
        $this->badgeDescription = $badgeDescription;
    }
    public function setXBronze(int $value): void {
        $this->xBronze = $value;
    }
    public function setXSilver(int $value): void {
        $this->xSilver = $value;
    }
    public function setXGold(int $value): void {
        $this->xGold = $value;
    }
}