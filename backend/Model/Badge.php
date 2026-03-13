<?php

namespace TTE\App\Model;

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
            $stmt->execute([":title" => $this->title, ":iconURL" => $this->iconURL, ":subtitle" => $this->subtitle,
                ":badgeDescription" => $this->badgeDescription, ":xBronze" => $this->xBronze,
                ":xSilver" => $this->xSilver, ":xGold" => $this->xGold]);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Method that creates a new Badge object
     * @param array $fields required
     * @return StoredObject the newly formed badge object
     * @throws DatabaseException|MissingValuesException
     */
    public static function create(array $fields): StoredObject
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
        $badge->setSubtitleAction($fields['subtitle']);
        $badge->setSubtitleSubject($fields['badgeDescription']);
        $badge->setProgression1(intval($fields['xBronze']));
        $badge->setProgression2(intval($fields['xSilver']));
        $badge->setProgression3(intval($fields['xGold']));

        // Create parameterised SQL command
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO badge (title, iconURL, subtitle, badgeDescription, xBronze, xSilver, xGold)
            VALUES (:title, :iconURL, :subtitle, :badgeDescription, :xBronze, :xSilver, :xGold);");

        // Attempt execution of SQL command, throwing database error if failed
        try {
            $stmt->execute([":title" => $badge->getTitle(), ":iconURL" => $badge->getIconURL(), ":subtitle" =>
                $badge->getSubtitleAction(), ":badgeDescription" => $badge->getSubtitleSubject(), ":xBronze" => $badge->getProgression1(),
                ":xSilver" => $badge->getProgression2(), ":xGold" => $badge->getProgression3()]);
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
     * Method loading a Badge object representing the one holding given id value
     * @param int $id
     * @return \TTE\App\Model\StoredObject
     * @throws DatabaseException
     */
    public static function load(int $id): StoredObject
    {
        // SQL statement formed and executed
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM badge WHERE id=:id;");
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
            throw new DatabaseException(("No badge with badge ID of $id"));
        }

        // Construct badge object and perform required data type conversions
        $badge = new Badge;
        $badge->id = $row["id"];
        $badge->setTitle($row['title']);
        $badge->setIconURL($row['iconURL']);
        $badge->setSubtitleAction($row['subtitle']);
        $badge->setSubtitleSubject($row['badgeDescription']);
        $badge->setProgression1(intval($row['xBronze']));
        $badge->setProgression2(intval($row['xSilver']));
        $badge->setProgression3(intval($row['xGold']));

        return $badge;
    }

    /**
     * Check badge with badge ID passed exists or not
     * @param int $id of badge who's existence is to be verified
     * @return bool return true if does exist false if not
     */
    public static function existsWithID(int $id): bool
    {
        // SQL parameterised statement
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM badge WHERE id=:id;");

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
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM badge WHERE id=:id;");

        try {
            $stmt->execute([":id" => $id]);
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
    public function getSubtitleAction(): string {
        return $this->subtitle;
    }
    public function getSubtitleSubject(): string {
        return $this->badgeDescription;
    }
    public function getProgression1(): int {
        return $this->xBronze;
    }
    public function getProgression2(): int {
        return $this->xSilver;
    }
    public function getProgression3(): int {
        return $this->xGold;
    }

    // Required setters
    public function setTitle(string $title): void {
        $this->title = $title;
    }
    public function setIconURL(string $iconURL): void {
        $this->iconURL = $iconURL;
    }
    public function setSubtitleAction(string $subtitle): void {
        $this->subtitle = $subtitle;
    }
    public function setSubtitleSubject(string $badgeDescription): void {
        $this->badgeDescription = $badgeDescription;
    }
    public function setProgression1(int $value): void {
        $this->xBronze = $value;
    }
    public function setProgression2(int $value): void {
        $this->xSilver = $value;
    }
    public function setProgression3(int $value): void {
        $this->xGold = $value;
    }
}