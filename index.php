<?php

require 'vendor/autoload.php';


use Carbon\Carbon;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geotools;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Psr\Log\LoggerInterface;



trait Drone
{
    protected $speed = 100;

    protected $milesPerUnit = 100;

    protected $fuelUnits  = 50.0;

    public function getMaximumFlightDistance()
    {
        return $this->fuelUnits * $this->milesPerUnit;
    }

    public function __toString()
    {
        return $this->name;
    }
}



class Abbott
{
    use Drone;

    public $name = 'Abbott';
}

class Costello
{
    use Drone;

    public $name = 'Costello';
}

class VerifierException extends \Exception
{

}

class TimeCostam
{
    public $offset;
    public $hours;
    public $minutes;

    function __construct($offset, $hours, $minutes)
    {
        $this->offset = $offset;
        $this->hours = $hours;
        $this->minutes = $minutes;
    }
}

class Verifier
{


    public static function utcOffset($utc)
    {
        $utc = str_replace(' ', '', $utc);

        if(preg_match('/^(?:UTC|UT|)(\+|\-)((?:\d{1,2})\:(?:\d{1,2})|(?:\d{1,2}))$/', $utc, $result))
        {   
            $x = explode(':', $result[2]);
            $hours = $x[0];
            $minutes = $x[1] ?? 0;

            $prefix = $result[1];

            $buildMe = $result[1] . sprintf("%02s:%02s", $hours, $minutes);

            if(in_array($buildMe, Verifier::$validUtc))
            {
                return new TimeCostam($minutes, $hours, $minutes);
            }
            else
            {
                throw new VerifierException("Given UTC offset {$utc} interpreted as UTC{$buildMe} is not valid");
            }
        }
        else
        {
            throw new VerifierException("Given UTC offset {$utc} is not valid");
        }
    }

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
            return new Coordinate([$latitude, $longitude]);
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
    protected $departureTimezoneDesc = "Departure location timezone as UTC time offset (eg. +03:00 or -04:30)";

    protected $destinationLatitudeName = "destination-latitude";
    protected $destinationLatitudeDesc = "Destination location latitude (eg. 51.8860)";

    protected $destinationLongitudeName = "destination-longitude";
    protected $destinationLongitudeDesc = "Destination location longitude (eg. 0.2388)";

    protected $destinationTimezoneName = "destination-timezone";
    protected $destinationTimezoneDesc = "Destination location timezone as UTC time offset (eg. +03:00 or -04:30)";

    private $departureCoords;
    private $departureOffset;
    private $destinationCoords;
    private $destinationOffset;
    private $availableDrones = array();

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
        $departLat  = $input->getArgument($this->departureLatitudeName);
        $departLon  = $input->getArgument($this->departureLongitudeName);
        $departTime = $input->getArgument($this->departureTimezoneName);
        $destLat    = $input->getArgument($this->destinationLatitudeName);
        $destLon    = $input->getArgument($this->destinationLongitudeName);
        $destTime   = $input->getArgument($this->destinationTimezoneName);

        $this->departureCoords   = Verifier::coords($departLat, $departLon);
        $this->destinationCoords = Verifier::coords($destLat, $destLon);
        $this->departureOffset   = Verifier::utcOffset($departTime);
        $this->destinationOffset = Verifier::utcOffset($destTime);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initDrones();

        $drones = $this->getDrones();

        if(empty($drones))
        {
            $io = new SymfonyStyle($input, $output);
            $io->error('No available drones for given distance. Terminating.');

            return;
        }

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Select one of the available drones (protip: you can use up/down arrows)',
            $drones
        );
        $question->setErrorMessage('Color %s is invalid.');

        $d = $helper->ask($input, $output, $question);

        $drones[$d];

        //$this->departureOffset
        //$this->destinationOffset

        $depart = Carbon::now()->addHours($prefix . $hours)->addMinutes($prefix . $minutes)->format('H:i');
        $dest = Carbon::now()->addHours($prefix . $hours)->addMinutes($prefix . $minutes)->format('H:i');

        

        $table = new Table($output);
        $table
            ->setHeaders(array('Chosen Drone', 'Departure Time', 'Arrival Time', 'Flight Duration', 'Flight Distance'))
            ->setRows(array(
                array($d, $this->dep, 'Dante Alighieri'),
            ))
        ;
        $table->render();
    }

    private function initDrones()
    {
        $this->availableDrones[] = new Abbott();
        $this->availableDrones[] = new Costello();
    }

    private function getDrones()
    {
        $distance = $this->calculcateDistance();

        $drones = [];

        foreach($this->availableDrones as $drone)
        {
            if($distance <= $drone->getMaximumFlightDistance())
            {
                $drones[$drone->name] = $drone;
            }
        }

        return $drones;

        foreach($drones as $drone)
        {
            //echo $drone->name, PHP_EOL;
        }
    }

    private function calculcateDistance()
    {
        $geotools = new Geotools();
        $dist = $geotools->distance()->setFrom($this->departureCoords)->setTo($this->destinationCoords);
        echo $dist->in('km')->flat(), PHP_EOL;
    }
}

$application = new Application('Heptapod Laundrone Flight System', '0.8');
$application->add(new AddFlight());
$application->run();