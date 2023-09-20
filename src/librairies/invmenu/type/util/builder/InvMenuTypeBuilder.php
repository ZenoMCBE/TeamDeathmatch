<?php

declare(strict_types=1);

namespace zenogames\librairies\invmenu\type\util\builder;

use zenogames\librairies\invmenu\type\InvMenuType;

interface InvMenuTypeBuilder{

	public function build() : InvMenuType;
}
