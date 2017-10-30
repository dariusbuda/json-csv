<?php
/**
 * Created by PhpStorm.
 * User: Darius Buda
 * Date: 10/23/2017
 * Time: 8:16 PM
 */

$_CONFIG_JSON_FOLDER_PATH = __DIR__.'/../json_input';
$_CONFIG_OUTPUT_FOLDER = __DIR__.'/../output';

$_CONFIG_EXPORT_TYPES = array(
    'csv' => array(
        'download_text' => 'CONVERT TO CSV',
        'class' => 'CSV'
    ),

    'tsv' => array(
        'download_text' => 'CONVERT TO TSV',
        'class' => 'TSV'
    ),

    'xml' => array(
        'download_text' => 'CONVERT TO XML',
        'class' => 'XML'
    ),
);
