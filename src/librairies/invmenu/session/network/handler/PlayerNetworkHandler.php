<?php

declare(strict_types=1);

namespace tdm\librairies\invmenu\session\network\handler;

use Closure;
use tdm\librairies\invmenu\session\network\NetworkStackLatencyEntry;

interface PlayerNetworkHandler{

	public function createNetworkStackLatencyEntry(Closure $then) : NetworkStackLatencyEntry;
}
