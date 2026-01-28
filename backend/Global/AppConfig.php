<?php
namespace TTE\App\Global;

final class AppConfig {
    const DB_PORT = "8886"; // If using MySQL/MariaDB default, change to 3306

    const DB_HOST = "127.0.0.1";

    const DB_USER = "root";

    const DB_PASSWORD = "root"; // If using XAMPP with default config, this should be blank.

    const DB_NAME = "TeamProjectApp";
}