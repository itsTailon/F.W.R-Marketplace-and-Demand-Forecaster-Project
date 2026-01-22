<?php

namespace TTE\App\Model;

/**
 * An interface to be implemented by (model) classes which correspond to database entities (e.g., User).
 */
interface StoredObject {

    /**
     * Saves changes to the corresponding database record.
     *
     * If a DB record does not yet exist, it will be created.
     *
     * @throws DatabaseException upon failure to save.
     * @return int the ID of the saved record.
     */
    public function save(): int;

    /**
     * Loads a record and returns an object representing it.
     *
     * @param int $id ID of the record to be loaded.
     *
     * @throws DatabaseException if no record exists with the given ID.
     * @return StoredObject
     */
    public static function load(int $id): StoredObject;

}