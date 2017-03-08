<?php

namespace Heptapod\Commands;

use Heptapod\Drone\DroneAbstract;
use Heptapod\Drone\Abbott;
use Heptapod\Drone\Costello;
use Heptapod\Database\DbLite;
use Heptapod\Verify\Verifier;

use League\Geotools\Geotools;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddFlight extends Command
{
    private $geotools;

    private $departureCoords;
    private $departureOffset;
    private $destinationCoords;
    private $destinationOffset;
    private $availableDrones;
    private $writer;
    private $input;
    private $output;
    private $database;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->printHeader();        

        $distance    = $this->getFlightDistance();
        $drones      = $this->getSuitableDrones($distance);

        do
        {
            $drone = $this->handleDroneSelection($drones, $distance);
        }
        while(!$this->writer->confirm('Do you want to launch selected drone? (^C to terminate)?', true));

        $this->database->storeFlight($drone, $this->departureCoords, $this->destinationCoords);

        $this->writer->success("{$drone->name} was launched into airspace!");
    }

    private function handleDroneSelection($drones, $distance)
    {
        $droneName = $this->promptDroneSelection($drones);

        $chosenDrone = $drones[$droneName];
        $chosenDrone->setFlightDistance($distance);
        $chosenDrone->calculateFlightTime();
        $chosenDrone->setTimeOffsets($this->departureOffset, $this->destinationOffset);

        $this->printFlightSummary($chosenDrone);

        return $chosenDrone;
    }

    private function printHeader()
    {
        $this->writer->title("{$this->getApplication()->getName()} v{$this->getApplication()->getVersion()}");

        $this->writer->text("Departure:   " . $this->geotools->convert($this->departureCoords)->toDMS() . " for " . $this->departureOffset);
        $this->writer->text("Destination: " . $this->geotools->convert($this->destinationCoords)->toDMS() . " for " . $this->destinationOffset);
    }

    private function printFlightSummary(DroneAbstract $drone)
    {
        $this->writer->newline(1);

        $table = new Table($this->output);
        $table
            ->setHeaders(array('Chosen Drone', 'Departure Time', 'Arrival Time', 'Flight Duration', 'Flight Distance'))
            ->setRows(
            [
                [
                    $drone->name, 
                    $drone->getDepartureTime(),
                    $drone->getDestinationTime(),
                    $drone->getFlightDuration(),
                    $drone->getFlightDistance(),
                ]
            ]);
        $table->render();

        $this->writer->newline(1);
    }


    private function promptDroneSelection($drones)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Select one of the available drones (protip: you can use up/down arrows)',
            $drones
        );
        $question->setErrorMessage('Drone %s is unavailable.');

        return $helper->ask($this->input, $this->output, $question);
    }


    private function getSuitableDrones($distance)
    {
        $this->writer->text("Distance:    " . round($distance, 2) . " miles");

        $drones = array_filter($this->availableDrones, function($drone) use ($distance)
        {
            return $distance <= $drone->getMaximumFlightDistance();
        });

        if(empty($drones))
        {
            $this->writer->error('No available drones for given distance. Terminating.');

            exit;
        }

        $this->writer->newline(1);

        return $drones;
    }   


    private function getFlightDistance()
    {
        $dist = $this->geotools->distance()->setFrom($this->departureCoords)->setTo($this->destinationCoords);

        return $dist->in('mi')->vincenty();
    }

    /* ------------------------------------------------------------------------------------------ */

    protected $commandName = 'flight:add';
    protected $commandDescription = 'Adds a drone flight';

    protected $departureLatitudeName = 'departure-latitude';
    protected $departureLatitudeDesc = 'Departure location latitude (eg. 51.8860)';

    protected $departureLongitudeName = 'departure-longitude';
    protected $departureLongitudeDesc = 'Departure location longitude (eg. 0.2388)';

    protected $departureTimezoneName = 'departure-timezone';
    protected $departureTimezoneDesc = 'Departure location timezone as UTC time offset (eg. +03:00 or -04:30)';

    protected $destinationLatitudeName = 'destination-latitude';
    protected $destinationLatitudeDesc = 'Destination location latitude (eg. 51.8860)';

    protected $destinationLongitudeName = 'destination-longitude';
    protected $destinationLongitudeDesc = 'Destination location longitude (eg. 0.2388)';

    protected $destinationTimezoneName = 'destination-timezone';
    protected $destinationTimezoneDesc = 'Destination location timezone as UTC time offset (eg. +03:00 or -04:30)';

    function __construct()
    {
        parent::__construct();

        $this->geotools = new Geotools();
    }

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
        $departLat    = $input->getArgument($this->departureLatitudeName);
        $departLon    = $input->getArgument($this->departureLongitudeName);
        $departOffset = $input->getArgument($this->departureTimezoneName);
        $destLat      = $input->getArgument($this->destinationLatitudeName);
        $destLon      = $input->getArgument($this->destinationLongitudeName);
        $destOffset   = $input->getArgument($this->destinationTimezoneName);

        $this->departureCoords   = Verifier::getCoordinates($departLat, $departLon);
        $this->destinationCoords = Verifier::getCoordinates($destLat, $destLon);

        $this->departureOffset   = Verifier::getTimeOffset($departOffset);
        $this->destinationOffset = Verifier::getTimeOffset($destOffset);

        $this->writer = new SymfonyStyle($input, $output);
        $this->input  = $input;
        $this->output = $output;

        $this->initDrones();

        $this->database = new DbLite();
    }


    private function initDrones()
    {
        $this->availableDrones = array();

        $this->availableDrones['Abbott']   = new Abbott();
        $this->availableDrones['Costello'] = new Costello();
    }
}