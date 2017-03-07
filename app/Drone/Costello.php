<?php

namespace Heptapod\Drone;

class Costello extends DroneAbstract
{
    public $name = 'Costello';

    protected $speed = 500;

    protected $milesPerUnit = 150;

    protected $fuelUnits  = 5.0;
}