<?php

namespace Heptapod\Drone;

class Abbott extends DroneAbstract
{
    public $name = 'Abbott';

    protected $speed = 30;

    protected $milesPerUnit = 10;

    protected $fuelUnits  = 150.0;
}