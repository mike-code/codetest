<?php

namespace Heptapod\Commands;

use Heptapod\Database\DbLite;
use Heptapod\Verify\Verifier;

use Carbon\Carbon;
use League\Geotools\Geotools;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListFlights extends Command
{
    protected $commandName = 'flight:list';
    protected $commandDescription = 'List all drone flights';

    private $database;
    private $writer;

    function __construct()
    {
        parent::__construct();

        $this->geotools = new Geotools();
    }

    protected function configure()
    {
        $this
            ->setName($this->commandName)
            ->setDescription($this->commandDescription);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->database = new DbLite();
        $this->writer   = new SymfonyStyle($input, $output);

        $this->writer->title("{$this->getApplication()->getName()} v{$this->getApplication()->getVersion()}");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $flights = $this->database->getFlights();

        if(empty($flights))
        {
            $this->writer->note("No flights found");
            return;
        }

        $data = array();

        foreach ($flights as $flight)
        {
            $className  = "Heptapod\\Drone\\{$flight['drone']}";
            $drone      = new $className();
            $droneFuel  = $drone->getFuelUnits();

            // View data

            $droneName           = $flight['drone'];
            $departureLocation   = $flight['start_location'];
            $destinationLocation = $flight['stop_location'];
            $departureTime       = Carbon::createFromTimestamp($flight['start_timestamp'])
                                    ->addSeconds(Verifier::getTimeOffset($flight['start_timezone'])->getSecondsOffset())
                                    ->format('H:i');
            $destinationTime     = Carbon::createFromTimestamp($flight['stop_timestamp'])
                                    ->addSeconds(Verifier::getTimeOffset($flight['stop_timezone'])->getSecondsOffset())
                                    ->format('H:i');
            $duration            = gmdate('H:i', $flight['duration']);
            $distance            = round($flight['distance'], 2) . " miles";
            $progress            = min(1, (Carbon::now()->timestamp - (int)$flight['start_timestamp']) / (int)$flight['duration']);
            $remainingFuel       = ($drone->getFuelUnits() - ($flight['distance'] * $progress) / $drone->getMilesPerUnit()) / $drone->getFuelUnits();

            $data[] = [
                $droneName,
                $departureLocation,
                $departureTime . " ({$flight['start_timezone']})",
                $distance,
                $duration,
                $destinationLocation,
                $destinationTime . " ({$flight['stop_timezone']})",
                round($progress * 100) . '%',
                round($remainingFuel * 100) . '%',
            ];
        }


        $table = new Table($output);
        $table
            ->setHeaders([
                'Drone',
                'Departure Location',
                'Departure Time',
                'Distance',
                'Duration',
                'Arrival Location',
                'Arrival Time',
                'Progress',
                'Remaining Fuel'
            ])
            ->setRows($data);
        $table->render();
    }
}