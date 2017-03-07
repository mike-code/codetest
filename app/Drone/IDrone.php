<?php

namespace Heptapod\Drone;

interface IDrone
{
    public function getName();
    public function getDepartureTimeRaw();
    public function getDestinationTimeRaw();
    public function getFlightDurationRaw();
    public function getFlightDistanceRaw();
    public function getDepartureOffset();
    public function getDestinationOffset();
}