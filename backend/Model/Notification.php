<?php


namespace TTE\App\Model;


class Notification extends StoredObject {
    private int $id;
    private int $userID;
    private string $title;
    private string $message;
    private bool $isRead;
    private string $createdAt;



    public function getID(): int { return $this->id; }
    public function getUserID(): int { return $this->userID; }
    public function getTitle(): string { return $this->title; }
    public function getMessage(): string { return $this->message; }
    public function getIsRead(): bool { return $this->isRead; }
    public function getCreatedAt(): string { return $this->createdAt; }

    public function setTitle(string $title): void { $this->title = $title; }
    public function setMessage(string $message): void { $this->message = $message; }
    public function setIsRead(bool $isRead): void { $this->isRead = $isRead; }

    
    /**
     * Creates and returns a notification object, and add a record to the database
     * 
     * @param array $fields
     * 
     * @return StoredObject
     * 
     * @throws DatabaseException|MissingValuesException
     */
    public static function create(array $fields): StoredObject {
        // Check required fields have values.
        if(!isset($fields['userID']) || !isset($fields['title']) || !isset($fields['message'])) {
            throw new MissingValuesException("Missing information to create notification");
        }

        // Create SQL statement to create Notification record
        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO notifications (userID, title, message) VALUES (:userID, :title, :message)");

        // Attempt to execute statement
        try {
            $stmt->execute([
                ":userID" => $fields['userID'],
                ":title" => $fields['title'],
                ":message" => $fields['message']
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // create notification object.
        $notification = new self();

        // get id of created notification and set it to notification object.
        $notification->id = DatabaseHandler::getPDO()->lastInsertId();

        // set fields of notification object.
        $notification->userID = $fields['userID'];
        $notification->title = $fields['title'];
        $notification->message = $fields['message'];
        $notification->isRead = false;

        // return notification object.
        return $notification;
    }



    /**
     * loads notification from database.
     * 
     * @param int $id
     * 
     * @throws NoSuchNotificationException
     * 
     * @throws DatabaseException
     * 
     * @return Notification
     * 
     */
    public static function load(int $id): Notification {

        // check notification exists with id.
        if(!self::existsWithID($id)) {
            throw new NoSuchNotificationException("No such notification with ID $id");
        }

        // create SQL statement to get notification with id provided.
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM notifications WHERE notificationID = :id");


        // Attempt to execute statement.
        try {
            $stmt->execute([":id" => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // create new notification object and set fields.
        $notification = new self();
        $notification->id = $row['notificationID'];
        $notification->userID = $row['userID'];
        $notification->title = $row['title'];
        $notification->message = $row['message'];
        $notification->isRead = (bool) $row['isRead'];
        $notification->createdAt = $row['createdAt'];

        // return new notification object.
        return $notification;
    }





    /**
     * Check if notification exists with provided id.
     * 
     * @param int $id
     * 
     * @throws DatabaseException
     * 
     * @return bool
     * 
     */
    public static function existsWithID(int $id): bool {

        // Create SQL statement to get all notifications with provided id.
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM notifications WHERE notificationID = :id");


        // Attempt to execute SQL statement.
        try {
            $stmt->execute([":id" => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }


        // return true / false depending on if there exists a notification with provided id.
        return !($row === false);

    }





    /**
     * Delete a notification from the database.
     * 
     * @param int $id
     * 
     * @throws NoSuchNotificationException
     * 
     * @throws DatabaseException
     * 
     * @return void
     * 
     */
    public static function delete(int $id): void {

        // Check if notification exists with object.
        if(!self::existsWithID($id)) {
            throw new NoSuchNotificationException("No such notification with ID $id");
        }

        // Create SQL statement to delete notification with provided id.
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM notifications WHERE notificationID = :id");
        

        // Attempt to execute sql statement.
        try {
            $stmt->execute([":id" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

    }



    /**
     * update database from current notification object.
     * 
     * @throws NoSuchNotificationException
     * 
     * @throws DatabaseException
     * 
     * @return void
     * 
     */
    public function update(): void {

        // check if current object has a valid notificationID.
        if(!self::existsWithID($this->id)) {
            throw new NoSuchNotificationException("No such notification with ID $this->id");
        }

        // Create SQL statement to update notification with new fields.
        $stmt = DatabaseHandler::getPDO()->prepare(
            "UPDATE notifications SET title = :title, message = :message, isRead = :isRead WHERE notificationID = :id"
        );

        // Attempt to execute sql statement.
        try {
            $stmt->execute([
                ":title" => $this->title,
                ":message" => $this->message,
                ":isRead" => $this->isRead,
                ":id" => $this->id
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }


    /**
     * get all notifications linked to a provided user.
     * 
     * @param int $userID
     * 
     * @param int $limit
     * 
     * @throws NoSuchAccountException
     * 
     * @throws DatabaseException
     * 
     * @return array
     */
    public static function getForUser(int $userID, int $limit = -1): array {

        // Check if account exists with provided user id.
        if(!Account::existsWithID($userID)) {
            throw new NoSuchAccountException("No such account with ID $userID");
        }

        // Create SQL statement to get all notifications linked to user.
        $sql = "SELECT * FROM notifications WHERE userID = :id ORDER BY createdAt DESC";
        if($limit > 0) {
            $sql = $sql . " LIMIT " . $limit;
        }
        $stmt = DatabaseHandler::getPDO()->prepare($sql);


        $rows = [];

        // Attempt to execute sql statement.
        try {
            $stmt->execute([
                ":id" => $userID
            ]);

            $rows =  $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }


        // return array of all notifications.
        return $rows;

    }

    /**
     * get all unread notifications linked to user.
     * 
     * @param int $userID
     * 
     * @param int $limit
     * 
     * @throws NoSuchAccountException
     * 
     * @throws DatabaseException
     * 
     * @return array
     * 
     */
    public static function getUnreadForUser(int $userID, int $limit = -1): array {

        

        // check if account exists with provided user id.
        if(!Account::existsWithID($userID)) {
            throw new NoSuchAccountException("No such account with ID $userID");
        }

        // create sql statement to get all notifications linked to user and are marked unread.

        $sql = "SELECT * FROM notifications WHERE userID = :id AND isRead = false ORDER BY createdAt DESC";
        if($limit > 0) {
            $sql = $sql . " LIMIT " . $limit;
        }
        $stmt = DatabaseHandler::getPDO()->prepare($sql);



        $rows = [];


        // Attempt to execute sql statement.
        try {
            $stmt->execute([
                ":id" => $userID
            ]);

            $rows =  $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // return all unread notifications.
        return $rows;

    }

    /**
     * get number of unread notifications.
     * 
     * @param int $userID
     * 
     * @return int
     * 
     */
    public static function getUnreadCount(int $userID): int {
        return count(self::getUnreadForUser($userID));
    }



    /**
     * Mark all unread notifications to read for user.
     * 
     * @param int $userID
     * 
     * @throws NoSuchAccountException
     * 
     * @throws DatabaseException
     * 
     * @return void
     * 
     */
    public static function markAllRead(int $userID): void {

        // check if account exists with provided user id.
        if(!Account::existsWithID($userID)) {
            throw new NoSuchAccountException("No such account with ID $userID");
        }

        // create SQL statement to update all unread notifications linked to user id to read.
        $stmt = DatabaseHandler::getPDO()->prepare("UPDATE notifications SET isRead = TRUE WHERE userID = :id AND isRead = FALSE");


        // Attempt to execute sql statement.
        try {
            $stmt->execute([
                ":id" => $userID
            ]);

        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

    }
}