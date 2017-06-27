<?php

namespace Demo\Resource;

use Fliglio\Routing\Routable;
use Fliglio\Web\Body;
use Fliglio\Web\PathParam;
use Fliglio\Web\GetParam;
use Fliglio\Web\Entity;


use Fliglio\Borg\BorgImplant;
use Fliglio\Borg\Chan;


class DemoResource {
	use BorgImplant;


	public function test() {
		$ch = $this->coll()->mkChan();
		$names = ["bob", "sally", "sue"];

		foreach ($names as $name) {
			$this->coll()->testDownstream($name, $ch);
		}
		$results = [];
		for ($i = 0; $i < count($names); $i++) {
			$results[] = $ch->get();
		}
		return $results;
	}

	public function testDownstream($name, Chan $ch) {
		$ch->add($name);
	}

}



