<?php

namespace Demo;

use Fliglio\Http\Http;
use Fliglio\Routing\Type\RouteBuilder;
use Fliglio\Fli\Configuration\DefaultConfiguration;
use Fliglio\Borg\Amqp\AmqpCollectiveDriver;
use Fliglio\Borg\Amqp\AmqpChanDriverFactory;
use Fliglio\Borg\Collective;
use Fliglio\Borg\Chan\ChanFactory;

use Fliglio\Consul\AddressProviderFactory;

use PhpAmqpLib\Connection\AMQPStreamConnection;

use Demo\Research\Scanner;
use Demo\Db\RaceDbm;
use Demo\Resource\LifeFormScanner;
use Demo\Resource\GroupScanner;
use Demo\Resource\Assimilation;


class DemoConfiguration extends DefaultConfiguration {

	public function getRoutes() {

		// Consul
		$apFactory = new AddressProviderFactory();

		// Rabbitmq
		$rabbitAp = $apFactory->createConsulAddressProvider('rabbitmq');
		$rAdd = $rabbitAp->getAddress();
		$rConn = new AMQPStreamConnection($rAdd->getHost(), $rAdd->getPort(), "guest", "guest", "/");
		
		// MySQL
		$mysqlAp = $apFactory->createConsulAddressProvider('mysql');
		$mAdd = $mysqlAp->getAddress();
		
		$dsn = sprintf("mysql:host=%s;dbname=borg", $mAdd->getHost());
		$db = new \PDO($dsn, 'borg', 'changeme', [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		// Resource Dependencies
		$http = null;
		$scanner = new Scanner($http);
		$dbm = new RaceDbm($db);

		// Resources
		$ls = new LifeFormScanner($scanner);
		$gs = new GroupScanner($scanner);
		$as = new Assimilation($dbm);

		// Borg
		$driver = new AmqpCollectiveDriver($rConn);

		$coll = new Collective($driver, "borg-demo", $_SERVER['CUBE_DC']);
		$coll->assimilate($ls);
		$coll->assimilate($gs);
		$coll->assimilate($dbm);
		

		return [
			// Life Form Scanner
			RouteBuilder::get()
				->uri('/life-form')
				->resource($ls, 'scan')
				->method(Http::METHOD_POST)
				->build(),
					
			// Group Scanner
			RouteBuilder::get()
				->uri('/group')
				->resource($gs, 'scan')
				->method(Http::METHOD_POST)
				->build(),
					
			// Assimilation
			RouteBuilder::get()
				->uri('/race/:race')
				->resource($as, 'assimilateRace')
				->method(Http::METHOD_PUT)
				->build(),
			RouteBuilder::get()
				->uri('/race/:race')
				->resource($as, 'getRaceStatus')
				->method(Http::METHOD_GET)
				->build(),
					
			// Router for all Borg Collective calls
			RouteBuilder::get()
				->uri('/borg')
				->resource($coll, "mux")
				->method(Http::METHOD_POST)
				->build(),
		];
	}
}


