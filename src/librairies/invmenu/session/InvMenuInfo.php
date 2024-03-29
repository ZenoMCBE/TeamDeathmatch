<?php

declare(strict_types=1);

namespace tdm\librairies\invmenu\session;

use tdm\librairies\invmenu\InvMenu;
use tdm\librairies\invmenu\type\graphic\InvMenuGraphic;

final class InvMenuInfo{

	public function __construct(
		readonly public InvMenu $menu,
		readonly public InvMenuGraphic $graphic,
		readonly public ?string $graphic_name
	){}
}
