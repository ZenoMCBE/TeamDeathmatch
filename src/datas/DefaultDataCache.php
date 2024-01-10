<?php

namespace tdm\datas;

use pocketmine\player\Player;

interface DefaultDataCache {

    /**
     * @param Player $player
     * @return void
     */
    public function setDefaultData(Player $player): void;

    /**
     * @return mixed
     */
    public function getDefaultData(): mixed;

}
