<?php

declare(strict_types=1);

namespace zenogames\librairies\invmenu\session\network\handler;

use Closure;
use zenogames\librairies\invmenu\session\network\NetworkStackLatencyEntry;

interface PlayerNetworkHandler{

	public function createNetworkStackLatencyEntry(Closure $then) : NetworkStackLatencyEntry;
}
