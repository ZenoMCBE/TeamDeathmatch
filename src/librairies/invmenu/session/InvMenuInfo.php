<?php

declare(strict_types=1);

namespace zenogames\librairies\invmenu\session;

use zenogames\librairies\invmenu\InvMenu;
use zenogames\librairies\invmenu\type\graphic\InvMenuGraphic;

final class InvMenuInfo{

	public function __construct(
		readonly public InvMenu $menu,
		readonly public InvMenuGraphic $graphic,
		readonly public ?string $graphic_name
	){}
}
