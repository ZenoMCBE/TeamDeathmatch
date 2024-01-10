<?php

namespace tdm\listeners;

use pocketmine\event\Listener;
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\Server;
use tdm\managers\LoadersManager;
use tdm\TeamDeathmatch;

final class PluginListeners implements Listener {

    /**
     * @param PluginDisableEvent $event
     * @return void
     */
    public function onDisable(PluginDisableEvent $event): void {
        if ($event->getPlugin()->getName() == TeamDeathmatch::getInstance()->getName()) {
            LoadersManager::getInstance()->unloadAll();
            foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                $onlinePlayer->kick("§l§q» §r§aRelancement d'une nouvelle partie ! §l§q«");
            }
        }
    }

}
