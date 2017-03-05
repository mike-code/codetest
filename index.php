<?php

require 'vendor/autoload.php';

use Carbon\Carbon;
use Commando\Command;
use League\Geotools;
use Geocoder\Model\Coordinates;

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
	private $command;
	private $tempLatitude;

	public function __construct(Command $c)
	{
		$this->command = $c;
	}

	public function check($method, $argument)
	{
		try
		{
			$this->{$method}($argument);
			return true;
		}
		catch(VerifierException $e)
		{
			echo "ERR: {$e->getMessage()}", PHP_EOL;
			return false;
		}
	}

	private function latitude($latitude)
	{
		if(!is_numeric($latitude))
		{
			throw new VerifierException("Latitude must be a number");
		}

		$this->tempLatitude = $latitude;
	}

	/**
	 * Longitude always comes after latitude
	 * 
	 * @param type $longitude 
	 * @return type
	 */
	private function longitude($longitude)
	{	
		if(!is_numeric($longitude))
		{
			throw new VerifierException("Longitude must be a number");
		}

		try
		{
			new Coordinates($this->tempLatitude, $longitude);
		}
		catch(Exception $e)
		{
			throw new VerifierException($e->getMessage());
		}
	}
}

class Main
{
	public $command;

	public function __construct()
	{
		$this->command = new Command();
		$v = new Verifier($this->command);

		$this->command->argument()->require()->describedAs('Departure location latitude (eg. 51.8860)')->must(function($value) use ($v) { return $v->check('latitude', $value); });
		$this->command->argument()->require()->describedAs('Departure location longitude (eg. 0.2388)')->must(function($value) use ($v) { return $v->check('longitude', $value); });
		$this->command->argument()->require()->describedAs('Departure location timezone as UTC time offset (eg. +3 or -6');
		//$this->command->argument()->require()->describedAs('Destination location latitude (eg. 51.8860)');
		//$this->command->argument()->require()->describedAs('Destination location longitude (eg. 0.2388)');
		//$this->command->argument()->require()->describedAs('Destination location timezone as UTC time offset (eg. +3 or -6');
	}
}

new Main();

// $cc->useDefaultHelp();

// latitude
// longitude
// timezone (UTC time offset)
// latitude
// longitude
// timezone (UTC time offset)