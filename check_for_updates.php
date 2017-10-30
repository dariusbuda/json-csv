<?php
/**
 * Created by PhpStorm.
 * User: Darius Buda
 * Date: 10/23/2017
 * Time: 8:14 PM
 */

require_once(__DIR__.'/config/config.php');
require_once(__DIR__.'/classes/Problem0.php');

try {
    CheckForUpdate::$jsonFolderPath = $_CONFIG_JSON_FOLDER_PATH;
    CheckForUpdate::$outputFolder = $_CONFIG_OUTPUT_FOLDER;
    CheckForUpdate::$exportTypes = $_CONFIG_EXPORT_TYPES;

    CheckForUpdate::run();
}
catch(Exception $ex) {
    echo $ex->getMessage();
}
