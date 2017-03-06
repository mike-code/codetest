<?php

require 'vendor/autoload.php';


use Carbon\Carbon;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Geotools\Coordinate\Coordinate;


interface Drone
{

}

class Abbott implements Drone
{

}

class Costello implements Drone
{

}

class X
{
    public function __construct()
    {
        $current = Carbon::now();
        $current = new Carbon();
    }
}

class VerifierException extends \Exception
{

}

class Verifier
{
    public static function coords($latitude, $longitude)
    {   
        if(!is_numeric($latitude))
        {
            throw new VerifierException("Latitude must be a number");
        }

        if(!is_numeric($longitude))
        {
            throw new VerifierException("Longitude must be a number");
        }

        try
        {
            $coordinate = new Coordinate([$latitude, $longitude]);
            echo $coordinate->getLatitude(), PHP_EOL;
        }
        catch(Exception $e)
        {
            throw new VerifierException($e->getMessage());
        }
    }
}

class AddFlight extends Command
{
    protected $commandName = 'flight:add';
    protected $commandDescription = "Adds a drone flight";

    protected $departureLatitudeName = "departure-latitude";
    protected $departureLatitudeDesc = "Departure location latitude (eg. 51.8860)";

    protected $departureLongitudeName = "departure-longitude";
    protected $departureLongitudeDesc = "Departure location longitude (eg. 0.2388)";

    protected $departureTimezoneName = "departure-timezone";
    protected $departureTimezoneDesc = "Departure location timezone as UTC time offset (eg. +3 or -6)";

    protected $destinationLatitudeName = "destination-latitude";
    protected $destinationLatitudeDesc = "Destination location latitude (eg. 51.8860)";

    protected $destinationLongitudeName = "destination-longitude";
    protected $destinationLongitudeDesc = "Destination location longitude (eg. 0.2388)";

    protected $destinationTimezoneName = "destination-timezone";
    protected $destinationTimezoneDesc = "Destination location timezone as UTC time offset (eg. +3 or -6)";

    private $departureCoords;
    private $destinationCoords;


    protected function configure()
    {
        $this
            ->setName($this->commandName)
            ->setDescription($this->commandDescription)
            ->addArgument(
                $this->departureLatitudeName,
                InputArgument::REQUIRED,
                $this->departureLatitudeDesc
            )
            ->addArgument(
                $this->departureLongitudeName,
                InputArgument::REQUIRED,
                $this->departureLongitudeDesc
            )
            ->addArgument(
                $this->departureTimezoneName,
                InputArgument::REQUIRED,
                $this->departureTimezoneDesc
            )
            ->addArgument(
                $this->destinationLatitudeName,
                InputArgument::REQUIRED,
                $this->destinationLatitudeDesc
            )
            ->addArgument(
                $this->destinationLongitudeName,
                InputArgument::REQUIRED,
                $this->destinationLongitudeDesc
            )
            ->addArgument(
                $this->destinationTimezoneName,
                InputArgument::REQUIRED,
                $this->destinationTimezoneDesc
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $departLat = $input->getArgument($this->departureLatitudeName);
        $departLon = $input->getArgument($this->departureLongitudeName);
        $departTime = $input->getArgument($this->departureTimezoneName);
        $destLat = $input->getArgument($this->destinationLatitudeName);
        $destLon = $input->getArgument($this->destinationLongitudeName);
        $destTime = $input->getArgument($this->destinationTimezoneName);

        $this->departureCoords = Verifier::coords($departLat, $departLon);
        //$this->destinationCoords = Verifier::coords($destLat, $destLon);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }
}

$application = new Application('Heptapod Laundrone Flight System', '0.8');
$application->add(new AddFlight());
$application->run();