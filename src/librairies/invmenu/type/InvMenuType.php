<?php

declare(strict_types=1);

namespace zenogames\librairies\invmenu\type;

use zenogames\librairies\invmenu\InvMenu;
use zenogames\librairies\invmenu\type\graphic\InvMenuGraphic;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

interface InvMenuType{

	public function createGraphic(InvMenu $menu, Player $player) : ?InvMenuGraphic;

	public function createInventory() : Inventory;
}
