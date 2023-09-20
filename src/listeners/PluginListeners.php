<?php

namespace zenogames\listeners;

use pocketmine\event\Listener;
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\Server;

final class PluginListeners implements Listener {

    /**
     * @param PluginDisableEvent $event
     * @return void
     * @noinspection PhpUnusedParameterInspection
     */
    public function onDisable(PluginDisableEvent $event): void {
        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $onlinePlayer->kick("§l§q» §r§aRelancement d'une nouvelle partie ! §l§q«");
        }
    }

}
