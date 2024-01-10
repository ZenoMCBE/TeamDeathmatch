<?php

namespace tdm\loaders\childs;

use tdm\listeners\{PluginListeners,
    BlockListeners,
    EntityListeners,
    InventoryListeners,
    PlayerListeners,
    ServerListeners,
    WorldListeners};
use tdm\loaders\Loader;
use pocketmine\event\Listener;
use tdm\TeamDeathmatch;

final class ListenersLoader implements Loader {

    /**
     * @return void
     */
    public function onLoad(): void {
        $plugin = TeamDeathmatch::getInstance();
        $listeners = [
            new BlockListeners(),
            new EntityListeners(),
            new InventoryListeners(),
            new PlayerListeners(),
            new PluginListeners(),
            new ServerListeners(),
            new WorldListeners()
        ];
        foreach ($listeners as $listener) {
            if (isset(class_implements($listener)[Listener::class])) {
                $plugin->getServer()->getPluginManager()->registerEvents($listener, $plugin);
            }
        }
        $plugin->getLogger()->notice("[Listener] " . count($listeners) . " listener(s) enregistré(s) !");
    }

    /**
     * @return void
     */
    public function onUnload(): void {}

}
