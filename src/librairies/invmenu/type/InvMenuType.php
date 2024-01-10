<?php

declare(strict_types=1);

namespace tdm\librairies\invmenu\type;

use tdm\librairies\invmenu\InvMenu;
use tdm\librairies\invmenu\type\graphic\InvMenuGraphic;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

interface InvMenuType{

	public function createGraphic(InvMenu $menu, Player $player) : ?InvMenuGraphic;

	public function createInventory() : Inventory;
}
