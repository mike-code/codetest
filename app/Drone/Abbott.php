<?php

namespace Heptapod\Drone;

class Abbott extends DroneAbstract
{
    public $name = 'Abbott';

    protected $speed = 100;

    protected $milesPerUnit = 100;

    protected $fuelUnits  = 50.0;
}