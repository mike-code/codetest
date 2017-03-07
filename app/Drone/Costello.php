<?php

namespace Heptapod\Drone;

class Costello extends DroneAbstract 
{
    public $name = 'Costello';

    protected $speed = 70;

    protected $milesPerUnit = 30;

    protected $fuelUnits  = 5.0;
}