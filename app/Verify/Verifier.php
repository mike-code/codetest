<?php

namespace Heptapod\Verify;

use Heptapod;
use Heptapod\Model\TimeOffset;
use League\Geotools\Coordinate\Coordinate;

class Verifier
{
    private static $validUtc =
    [
        '-12:00', '-11:00', '-10:00', '-09:00', '-08:00', '-07:00', '-06:00', 
        '-05:00', '-04:00', '-03:00', '-02:30', '-02:00', '-01:00', '+00:00', 
        '-00:00', '+01:00', '+02:00', '+03:00', '+04:00', '+05:00', '+05:20', 
        '+06:00', '+07:00', '+07:30', '+07:45', '+08:00', '+08:30', '+09:00', 
        '+10:00', '+11:00', '+12:00', 
    ];

    public static function getTimeOffset($offset)
    {
        $offset = str_replace(' ', '', $offset);

        if(preg_match('/^(?:UTC|UT|)(\+|\-)((?:\d{1,2})\:(?:\d{1,2})|(?:\d{1,2}))$/', $offset, $result))
        {   
            $x = explode(':', $result[2]);

            $hours = $x[0];
            $minutes = $x[1] ?? 0;
            $prefix = $result[1];

            $fullUtcOffset = $prefix . sprintf("%02s:%02s", $hours, $minutes);

            if(in_array($fullUtcOffset, self::$validUtc))
            {
                return new TimeOffset($fullUtcOffset, $hours, $minutes, $prefix);
            }
            else
            {
                throw new VerifierException("Given UTC offset {$offset} interpreted as UTC{$fullUtcOffset} is not valid");
            }
        }
        else
        {
            throw new VerifierException("Given UTC offset {$offset} is not valid");
        }
    }

    public static function getCoordinates($latitude, $longitude)
    {   
        if(!is_numeric($latitude))
        {
            throw new VerifierException("Latitude must be a number");
        }

        if(!is_numeric($longitude))
        {
            echo $longitude;
            throw new VerifierException("Longitude must be a number");
        }

        try
        {
            return new Coordinate([$latitude, $longitude]);
        }
        catch(\Exception $e)
        {
            throw new VerifierException($e->getMessage());
        }
    }
}