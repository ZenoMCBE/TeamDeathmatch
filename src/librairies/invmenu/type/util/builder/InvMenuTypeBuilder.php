<?php

declare(strict_types=1);

namespace tdm\librairies\invmenu\type\util\builder;

use tdm\librairies\invmenu\type\InvMenuType;

interface InvMenuTypeBuilder{

	public function build() : InvMenuType;
}
