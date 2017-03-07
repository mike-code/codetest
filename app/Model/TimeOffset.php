<?php

namespace Heptapod\Model;

class TimeOffset
{
    private $offset;
    private $hours;
    private $minutes;
    private $prefix;

    function __construct($offset, $hours, $minutes, $prefix)
    {
        $this->offset = $offset;
        $this->hours = $hours;
        $this->minutes = $minutes;
        $this->prefix = $prefix;
    }

    public function getSecondsOffset()
    {
        $seconds = ((int) $this->hours) * 3600 + ((int) $this->minutes) * 60;

        return $this->prefix . (string) $seconds;
    }

    public function __toString()
    {
        return "UTC{$this->prefix}" . sprintf("%02s:%02s", $this->hours, $this->minutes);
    }
}