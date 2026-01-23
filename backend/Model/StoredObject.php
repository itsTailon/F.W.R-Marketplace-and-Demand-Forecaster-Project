<?php

namespace TTE\App\Model;

/**
 * An interface to be implemented by (model) classes which correspond to database entities (e.g., User).
 */
abstract class StoredObject {

    /**
     * Empty constructor to enforce consistent interface for record retrieval and creation via dedicated methods.
     */
    protected final function __construct() {}

    /**
     * Saves changes to an existing database record.
     *
     * @throws DatabaseException upon failure to save.
     */
    public abstract function update(): void;

    /**
     * Creates a new database record and returns an object representing it.
     *
     * @throws DatabaseException upon failure.
     * @return StoredObject an object representing the record created.
     */
    public abstract function create(): StoredObject;

    /**
     * Loads a record and returns an object representing it.
     *
     * @param int $id ID of the record to be loaded.
     *
     * @throws DatabaseException if no record exists with the given ID.
     * @return StoredObject an object representing the record loaded.
     */
    public abstract static function load(int $id): StoredObject;

}