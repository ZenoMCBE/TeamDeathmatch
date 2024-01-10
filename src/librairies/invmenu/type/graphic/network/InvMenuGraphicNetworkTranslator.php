<?php

declare(strict_types=1);

namespace tdm\librairies\invmenu\type\graphic\network;

use tdm\librairies\invmenu\session\InvMenuInfo;
use tdm\librairies\invmenu\session\PlayerSession;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;

interface InvMenuGraphicNetworkTranslator{

	public function translate(PlayerSession $session, InvMenuInfo $current, ContainerOpenPacket $packet) : void;
}
