<?php

namespace zenogames;

use zenogames\managers\GameManager;
use zenogames\managers\LoadersManager;
use zenogames\utils\Constants;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use zenostats\ZenoStats;

final class Zeno extends PluginBase {

    use SingletonTrait;

    /**
     * TODO :
     * - Trouver une alternative pour l'animation de mort
     */

    /**
     * @return void
     */
    protected function onLoad(): void {
        self::setInstance($this);
        $this->getServer()->getNetwork()->setName(Constants::NAME);
        $this->getServer()->getNetwork()->unblockAddress("184.162.26.115");
    }

    /**
     * @return void
     */
    protected function onEnable(): void {
        LoadersManager::getInstance()->loadAll();
        GameManager::getInstance()->loadDefaultParameters();
        $this->getLogger()->notice("Zeno TDM a été activé avec succès !");
    }

    /**
     * @return void
     */
    protected function onDisable(): void {
        LoadersManager::getInstance()->unloadAll();
        $this->getLogger()->notice("Zeno TDM a été désactivé avec succès !");
    }

    /**
     * @return ZenoStats
     */
    public function getStatsApi(): ZenoStats {
        return ZenoStats::getInstance();
    }

}
