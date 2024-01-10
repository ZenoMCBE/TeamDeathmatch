<?php

namespace tdm;

use stats\Stats;
use tdm\managers\LoadersManager;
use tdm\utils\Constants;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

final class TeamDeathmatch extends PluginBase {

    use SingletonTrait;

    /**
     * @return void
     */
    protected function onLoad(): void {
        self::setInstance($this);
        $this->getServer()->getNetwork()->setName(Constants::NAME);
    }

    /**
     * @return void
     */
    protected function onEnable(): void {
        LoadersManager::getInstance()->loadAll();
        $this->getLogger()->notice("TeamDeathmatch activé.");
    }

    /**
     * @return void
     */
    protected function onDisable(): void {
        $this->getLogger()->notice("TeamDeathmatch désactivé.");
    }

    /**
     * @return Stats
     */
    public function getStatsApi(): Stats {
        return Stats::getInstance();
    }

}
