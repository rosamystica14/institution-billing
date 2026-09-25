<?php
/**
 * Database Connection Handler (PDO + SQLite)
 */

require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $instance = null;

    /**
     * Returns a singleton PDO connection to the SQLite database.
     * Automatically initializes the schema if the database file does not exist yet.
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dbExists = file_exists(DB_PATH);

            try {
                self::$instance = new PDO('sqlite:' . DB_PATH);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$instance->exec('PRAGMA foreign_keys = ON');
            } catch (PDOException $e) {
                die('Database connection failed: ' . $e->getMessage());
            }

            if (!$dbExists) {
                self::initializeSchema();
            }
        }

        return self::$instance;
    }

    /**
     * Runs the schema.sql file to create all tables on first run.
     */
    private static function initializeSchema(): void
    {
        $schemaFile = BASE_PATH . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            self::$instance->exec($sql);
        }

        $seedFile = BASE_PATH . '/database/seed.sql';
        if (file_exists($seedFile)) {
            $sql = file_get_contents($seedFile);
            self::$instance->exec($sql);
        }
    }
}
