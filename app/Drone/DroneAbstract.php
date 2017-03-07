<?php

namespace Heptapod\Drone;

use Carbon\Carbon;

use Heptapod\Model\TimeOffset;

abstract class DroneAbstract
{
    /**
     * Drone's name
     * 
     * @var string
     */
	protected $name;

    /**
     * Speed of drone in miles per hour.
     *
     * @var int
     */
    protected $speed;

    /**
     * Number of miles the drone can cover for
     * every unit of fuel.
     *
     * @var int
     */
    protected $milesPerUnit;

    /**
     * Units of fuel that the drone has.
     * 
     * @var float
     */
    protected $fuelUnits;

    /**
     * Distance (in miles) that the drone is
     * going to travel.
     * 
     * @var double
     */
    private $distance;

    /**
     * Total flight time (in seconds) that the
     * drone is going to travel.
     * 
     * @var int
     */
    private $flightTime;

    private $destinationTime;

    private $departureTime;

    private $departureOffset;

    private $destinationOffset;

    public function getMaximumFlightDistance()
    {
        return $this->fuelUnits * $this->milesPerUnit;
    }

    public function setFlightDistance($distance)
    {
        $this->distance = $distance;
    }

    public function getFlightDuration()
    {
        return gmdate('H:i', $this->flightTime);
    }

    public function getFlightDistance()
    {
        return round($this->distance, 2) . " miles";
    }

    public function getDepartureTime()
    {
        return $this->getTime($this->departureTime, $this->departureOffset);        
    }

    public function getDestinationTime()
    {
        return $this->getTime($this->destinationTime, $this->destinationOffset);
    }

    public function setTimeOffsets(TimeOffset $departureOffset, TimeOffset $destinationOffset)
    {
        $this->departureOffset = $departureOffset;
        $this->destinationOffset = $destinationOffset;
    }

    public function calculateFlightTime()
    {
        $this->flightTime = (int) round(($this->distance / $this->speed) * 3600);

        $this->departureTime = Carbon::now()->timestamp;
        $this->destinationTime = $this->departureTime + $this->flightTime;
    }

    public function __toString()
    {
        return $this->name;
    }

    private function getTime($timestamp, TimeOffset $offset)
    {
        $time = Carbon::createFromTimestamp($timestamp)
            ->addSeconds($offset->getSecondsOffset())
            ->format('H:i');

        return "{$time} ({$offset})";
    }
}