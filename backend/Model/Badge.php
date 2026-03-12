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
    private string $iconURL;
    private string $subtitle_action;
    private string $subtitle_subject;
    private int $progression_1;
    private int $progression_2;
    private int $progression_3;



    public function update(): void
    {
        // Check badge exists
        if (!isset($this->title) || !isset($this->iconURL) || !isset($this->subtitle_action) ||
            !isset($this->subtitle_subject) || !isset($this->progression_1) || !isset($this->progression_2)
            || !isset($this->progression_3) || empty(trim($this->title)) || empty(trim($this->iconURL)) ||
            empty(trim($this->subtitle_action)) || empty(trim($this->subtitle_subject))) {
            // Throw error
            throw new MissingValuesException("Missing required information to create a badge.");
        }

        // SQL parameterised query
        $stmt = DatabaseHandler::getPDO()->prepare("UPDATE badge SET title= :title, iconURL = :iconURL, 
        subtitle_action = :subtitle_action, subtitle_subject = :subtitle_subject, progression_1 = :progression_1, 
        progression_2 = :progression_2, progression_3 = :progression_3;");

        try {
            $stmt->execute([":title" => $this->title, ":iconURL" => $this->iconURL, ":subtitle_action" => $this->subtitle_action,
                ":subtitle_subject" => $this->subtitle_subject, ":progression_1" => $this->progression_1,
                ":progression_2" => $this->progression_2, ":progression_3" => $this->progression_3]);
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
        if (!isset($fields['title']) || !isset($fields['iconURL']) || !isset($fields['subtitle_action']) ||
            !isset($fields['subtitle_subject']) || !isset($fields['progression_1']) || !isset($fields['progression_2'])
            || !isset($fields['progression_3']) || empty(trim($fields['title'])) || empty(trim($fields['iconURL'])) ||
            empty(trim($fields['subtitle_action'])) || empty(trim($fields['subtitle_subject']))) {
            // Throw error
            throw new MissingValuesException("Missing required information to create a badge.");
        }

        // Create new badge object
        $badge = new Badge();
        $badge->setTitle($fields['title']);
        $badge->setIconURL($fields['iconURL']);
        $badge->setSubtitleAction($fields['subtitle_action']);
        $badge->setSubtitleSubject($fields['subtitle_subject']);
        $badge->setProgression1(intval($fields['progression_1']));
        $badge->setProgression2(intval($fields['progression_2']));
        $badge->setProgression3(intval($fields['progression_3']));

        // Create parameterised SQL command
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO badge (title, iconURL, subtitle_action, subtitle_subject, progression_1, progression_2, progression_3)
            VALUES (:title, :iconURL, :subtitle_action, :subtitle_subject, :progression_1, :progression_2, :progression_3);");

        // Attempt execution of SQL command, throwing database error if failed
        try {
            $stmt->execute([":title" => $badge->getTitle(), ":iconURL" => $badge->getIconURL(), ":subtitle_action" =>
                $badge->getSubtitleAction(), ":subtitle_subject" => $badge->getSubtitleSubject(), ":progression_1" => $badge->getProgression1(),
                ":progression_2" => $badge->getProgression2(), ":progression_3" => $badge->getProgression3()]);
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
        $badge->setSubtitleAction($row['subtitle_action']);
        $badge->setSubtitleSubject($row['subtitle_subject']);
        $badge->setProgression1(intval($row['progression_1']));
        $badge->setProgression2(intval($row['progression_2']));
        $badge->setProgression3(intval($row['progression_3']));

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
        return $this->subtitle_action;
    }
    public function getSubtitleSubject(): string {
        return $this->subtitle_subject;
    }
    public function getProgression1(): int {
        return $this->progression_1;
    }
    public function getProgression2(): int {
        return $this->progression_2;
    }
    public function getProgression3(): int {
        return $this->progression_3;
    }

    // Required setters
    public function setTitle(string $title): void {
        $this->title = $title;
    }
    public function setIconURL(string $iconURL): void {
        $this->iconURL = $iconURL;
    }
    public function setSubtitleAction(string $subtitle_action): void {
        $this->subtitle_action = $subtitle_action;
    }
    public function setSubtitleSubject(string $subtitle_subject): void {
        $this->subtitle_subject = $subtitle_subject;
    }
    public function setProgression1(int $value): void {
        $this->progression_1 = $value;
    }
    public function setProgression2(int $value): void {
        $this->progression_2 = $value;
    }
    public function setProgression3(int $value): void {
        $this->progression_3 = $value;
    }
}