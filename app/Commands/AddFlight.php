<?php

namespace Heptapod\Commands;

use Heptapod\Drone\DroneAbstract;
use Heptapod\Drone\Abbott;
use Heptapod\Drone\Costello;
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
    private $departureCoords;
    private $departureOffset;
    private $destinationCoords;
    private $destinationOffset;
    private $availableDrones;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $distance    = $this->getFlightDistance();
        $drones      = $this->getSuitableDrones($distance);
        $droneName   = $this->promptDroneSelection($drones, $input, $output);

        $chosenDrone = $drones[$droneName];
        $chosenDrone->setFlightDistance($distance);
        $chosenDrone->calculateFlightTime();
        $chosenDrone->setTimeOffsets($this->departureOffset, $this->destinationOffset);

        $this->printFlightSummary($chosenDrone, $output);
    }


    private function printFlightSummary(DroneAbstract $drone, OutputInterface $output)
    {
        $table = new Table($output);
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
    }


    private function promptDroneSelection($drones, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Select one of the available drones (protip: you can use up/down arrows)',
            $drones
        );
        $question->setErrorMessage('Drone %s is unavailable.');

        return $helper->ask($input, $output, $question);
    }


    private function getSuitableDrones($distance)
    {
        $drones = array_filter($this->availableDrones, function($value) use ($distance)
        {
            return $distance <= $value->getMaximumFlightDistance();
        });

        if(empty($drones))
        {
            $io = new SymfonyStyle($input, $output);
            $io->error('No available drones for given distance. Terminating.');

            exit;
        }

        return $drones;
    }   


    private function getFlightDistance()
    {
        $geotools = new Geotools();

        $dist = $geotools->distance()->setFrom($this->departureCoords)->setTo($this->destinationCoords);

        return $dist->in('mi')->vincenty();
    }

    /* ------------------------------------------------------------------------------------------ */

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
        $departOffset = $input->getArgument($this->departureTimezoneName);
        $destLat    = $input->getArgument($this->destinationLatitudeName);
        $destLon    = $input->getArgument($this->destinationLongitudeName);
        $destOffset   = $input->getArgument($this->destinationTimezoneName);

        $this->departureCoords   = Verifier::getCoordinates($departLat, $departLon);
        $this->destinationCoords = Verifier::getCoordinates($destLat, $destLon);

        $this->departureOffset   = Verifier::getTimeOffset($departOffset);
        $this->destinationOffset = Verifier::getTimeOffset($destOffset);

        $this->initDrones();
    }


    private function initDrones()
    {
        $this->availableDrones = array();

        $this->availableDrones['Abbott']   = new Abbott();
        $this->availableDrones['Costello'] = new Costello();
    }
}