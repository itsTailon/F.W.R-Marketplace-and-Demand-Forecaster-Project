<?php


namespace TTE\App\Model;


class SellerAction extends StoredObject {
    private int $id;
    private int $sellerID;
    private string $action;
    private string $reason;
    private string $createdAt;

    public function getID(): int { return $this->id; }
    public function getSellerID(): int { return $this->sellerID; }
    public function getAction(): string { return $this->action; }
    public function getReason(): string { return $this->reason; }
    public function getCreatedAt(): string { return $this->createdAt; }



    /**
     * 
     * Creates and returns a sellerAction object, and add a record to the database
     * 
     * @param array $fields
     * 
     * @throws MissingValuesException
     * 
     * @throws DatabaseException
     * 
     * @return SellerAction
     * 
     */
    public static function create(array $fields): StoredObject {
        if(!isset($fields['sellerID']) || !isset($fields['action']) || !isset($fields['reason'])) {
            throw new MissingValuesException("Missing information to create SellerAction");
        }

        $stmt = DatabaseHandler::getPDO()->prepare("INSERT INTO seller_actions (sellerID, action, reason) VALUES (:sellerID, :action, :reason)");

        // Attempt to execute statement
        try {
            $stmt->execute([
                ":sellerID" => $fields['sellerID'],
                ":action" => $fields['action'],
                ":reason" => $fields['reason']
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        $sellerAction = new self();

        $sellerAction->id = DatabaseHandler::getPDO()->lastInsertId();
        $sellerAction->sellerID = $fields['sellerID'];
        $sellerAction->action = $fields['action'];
        $sellerAction->reason = $fields['reason'];
        

        return $sellerAction;



    }


    /**
     * Check if sellerAction exists with provided id.
     * 
     * @param int $id
     * 
     * @throws DatabaseException
     * 
     * @return bool
     * 
     */
    public static function existsWithID(int $id): bool {

        // Create SQL statement to get all sellerActions with provided id.
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM seller_actions WHERE actionID = :id");


        // Attempt to execute SQL statement.
        try {
            $stmt->execute([":id" => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }


        // return true / false depending on if there exists a seller aciton with provided id.
        return !($row === false);
    }

        /**
     * Delete a seller action from the database.
     * 
     * @param int $id
     * 
     * @throws NoSuchSellerActionException
     * 
     * @throws DatabaseException
     * 
     * @return void
     * 
     */
    public static function delete(int $id): void {

        // Check if seller action exists with object.
        if(!self::existsWithID($id)) {
            throw new NoSuchSellerActionException("No such seller action with ID $id");
        }

        // Create SQL statement to delete sellerAction with provided id.
        $stmt = DatabaseHandler::getPDO()->prepare("DELETE FROM seller_actions WHERE actionID = :id");
        

        // Attempt to execute sql statement.
        try {
            $stmt->execute([":id" => $id]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

    }

    /**
     * update database from current sellerAction object.
     * 
     * @throws NoSuchSellerActionException
     * 
     * @throws DatabaseException
     * 
     * @return void
     * 
     */
    public function update(): void {

        // check if current object has a valid SellerAction.
        if(!self::existsWithID($this->id)) {
            throw new NoSuchSellerActionException("No such SellerAction with ID $this->id");
        }

        // Create SQL statement to update SellerAction with new fields.
        $stmt = DatabaseHandler::getPDO()->prepare(
            "UPDATE seller_actions SET action = :action, reason = :reason WHERE actionID = :id"
        );

        // Attempt to execute sql statement.
        try {
            $stmt->execute([
                ":action" => $this->action,
                ":reason" => $this->reason,
                ":id" => $this->id
            ]);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }


        /**
     * loads SellerAction from database.
     * 
     * @param int $id
     * 
     * @throws NoSuchSellerActionException
     * 
     * @throws DatabaseException
     * 
     * @return SellerAction
     * 
     */
    public static function load(int $id): SellerAction {

        // check sellerAction exists with id.
        if(!self::existsWithID($id)) {
            throw new NoSuchSellerActionException("No such SellerAction with ID $id");
        }

        // create SQL statement to get SellerAction with id provided.
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM seller_actions WHERE actionID = :id");


        // Attempt to execute statement.
        try {
            $stmt->execute([":id" => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }

        // create new sellerAction object and set fields.
        $sellerAction = new self();
        $sellerAction->id = $row['actionID'];
        $sellerAction->sellerID = $row['sellerID'];
        $sellerAction->action = $row['action'];
        $sellerAction->reason = $row['reason'];
        $sellerAction->createdAt = $row['createdAt'];

        // return new sellerAction object.
        return $sellerAction;
    }

        /**
     * get all SellerActions linked to a provided seller.
     * 
     * @param int $sellerID
     * 
     * @throws NoSuchAccountException
     * 
     * @throws DatabaseException
     * 
     * @return array
     */
    public static function getForSeller(int $sellerID): array {

        // Check if account exists with provided user id.
        if(!Account::existsWithID($sellerID)) {
            throw new NoSuchAccountException("No such account with ID $sellerID");
        }

        // Create SQL statement to get all sellerActions linked to seller.
        $stmt = DatabaseHandler::getPDO()->prepare("SELECT * FROM seller_actions WHERE sellerID = :id ORDER BY createdAt DESC");


        $rows = [];

        // Attempt to execute sql statement.
        try {
            $stmt->execute([
                ":id" => $sellerID
            ]);

            $rows =  $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }


        // return array of all sellerActions.
        return $rows;

    }

}


?>