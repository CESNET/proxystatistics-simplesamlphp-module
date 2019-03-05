<?php
/**
 *
 * Script for migrate statistics data from version < 1.6.x to version > 2.0.0
 *
 * You need firstly export the tables identityProviders and serviceProviders into two separate CSV files.
 *
 * The result file is in text format as SQL inserts.
 *
 * How to run this script:
 * php -f migrate_2.0.php
 *
 * @author: Pavel Vyskocil <vyskocilpavel@muni.cz>
 */

// Absolute path to CSV file with data about identityProviders
$identityProvidersFileName = '';

// Absolute path to CSV file with data about serviceProviders
$serviceProvidersFileName = '';

// Absolute path where result file will be stored
$resultFileName = '';

if (empty($identityProvidersFileName) || empty($serviceProvidersFileName) || empty($resultFileName)) {
    exit("One of required attributes is empty." . PHP_EOL);
}

$tableName = 'statistics';

$result = '';
$line = null;

// Identity providers part
$file = fopen($identityProvidersFileName, "r");

while (!feof($file)) {
    $line = (fgetcsv($file));
    if ($line != null) {
        $lineInsert = 'INSERT INTO ' . $tableName . '(year, month, day, sourceIdp, service, count) ' .
            'VALUES(' . $line[0] . ', ' . $line[1] . ', ' . $line[2] . ', "' . $line[3] . '","" , ' . $line[4] . ');' .
            PHP_EOL;
        $result .= $lineInsert;
    }
}

fclose($file);

// Service providers part
$file = fopen($serviceProvidersFileName, "r");

while (!feof($file)) {
    $line = (fgetcsv($file));
    if ($line != null) {
        $lineInsert = 'INSERT INTO ' . $tableName . '(year, month, day, sourceIdp, service, count) ' .
            'VALUES(' . $line[0] . ', ' . $line[1] . ', ' . $line[2] . ', "", "' . $line[3] . '", ' . $line[4] . ');' .
            PHP_EOL;
        $result .= $lineInsert;
    }
}

fclose($file);

// save to result file
file_put_contents($resultFileName, $result);
