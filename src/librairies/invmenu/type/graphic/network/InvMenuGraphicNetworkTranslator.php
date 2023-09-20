<?php

declare(strict_types=1);

namespace zenogames\librairies\invmenu\type\graphic\network;

use zenogames\librairies\invmenu\session\InvMenuInfo;
use zenogames\librairies\invmenu\session\PlayerSession;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;

interface InvMenuGraphicNetworkTranslator{

	public function translate(PlayerSession $session, InvMenuInfo $current, ContainerOpenPacket $packet) : void;
}
