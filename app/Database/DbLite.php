<?php

namespace Heptapod\Database;

use Heptapod\Drone\IDrone;
use League\Geotools\Coordinate\Coordinate;
use Heptapod\Model\TimeOffset;
use League\Geotools\Geotools;

class DbLite
{
    private $tableName = 'flights';
    private $filePath  = 'db.sqlite';
    private $db;

    function __construct()
    {
        $this->db = new \PDO('sqlite:' . $this->filePath);
        $this->create_table();
    }

    function create_table()
    {
        try
        {
            $this->db->exec(
                "CREATE TABLE IF NOT EXISTS {$this->tableName}
                (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    drone VARCHAR(255) NOT NULL,
                    start_timestamp INTEGER NOT NULL,
                    stop_timestamp INTEGER NOT NULL,
                    duration INTEGER NOT NULL,
                    distance VARCHAR(255) NOT NULL,
                    start_location VARCHAR(255) NOT NULL,
                    stop_locaion VARCHAR(255) NOT NULL,
                    start_timezone VARCHAR(255) NOT NULL,
                    stop_timezone VARCHAR(255) NOT NULL
                );
                CREATE UNIQUE INDEX flights_id_uindex ON {$this->tableName} (id);"
            );
        }
        catch( Exception $e)
        {
            dump($e);
        }
    }

    function storeFlight(IDrone $drone, Coordinate $departureCoords, Coordinate $destinationCoords)
    {
        $geotools = new Geotools();

        $query = $this->db->prepare(
            "INSERT INTO {$this->tableName} (
                    drone,
                    start_timestamp,
                    stop_timestamp,
                    duration,
                    distance,
                    start_location,
                    stop_locaion,
                    start_timezone,
                    stop_timezone
                ) VALUES (
                    :drone,
                    :startTimestamp,
                    :stopTimestamp,
                    :duration,
                    :distance,
                    :startLocation,
                    :stopLocation,
                    :startZone,
                    :stopZone
                );"
        );

        $droneName         = $drone->getName();
        $startTimestamp    = $drone->getDepartureTimeRaw();
        $stopTimestamp     = $drone->getDestinationTimeRaw();
        $duration          = $drone->getFlightDurationRaw();
        $distance          = $drone->getFlightDistanceRaw();
        $startlocation     = $geotools->convert($departureCoords)->toDMS();
        $stoplocation      = $geotools->convert($destinationCoords)->toDMS();
        $departureOffset   = (string) $drone->getDepartureOffset();
        $destinationOffset = (string) $drone->getDestinationOffset();

        $query->bindParam(':drone',          $droneName,         \PDO::PARAM_STR);
        $query->bindParam(':startTimestamp', $startTimestamp,    \PDO::PARAM_INT);
        $query->bindParam(':stopTimestamp',  $stopTimestamp,     \PDO::PARAM_INT);
        $query->bindParam(':duration',       $duration,          \PDO::PARAM_INT);
        $query->bindParam(':distance',       $distance,          \PDO::PARAM_STR);
        $query->bindParam(':startLocation',  $startlocation,     \PDO::PARAM_STR);
        $query->bindParam(':stopLocation',   $stoplocation,      \PDO::PARAM_STR);
        $query->bindParam(':startZone',      $departureOffset,   \PDO::PARAM_STR);
        $query->bindParam(':stopZone',       $destinationOffset, \PDO::PARAM_STR);

        $query->execute();

    }
}

