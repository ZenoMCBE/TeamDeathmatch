<?php

namespace zenogames\loaders\childs;

use zenogames\listeners\PluginListeners;
use zenogames\Zeno;
use zenogames\listeners\BlockListeners;
use zenogames\listeners\EntityListeners;
use zenogames\listeners\InventoryListeners;
use zenogames\listeners\PlayerListeners;
use zenogames\listeners\ServerListeners;
use zenogames\listeners\WorldListeners;
use zenogames\loaders\Loader;
use pocketmine\event\Listener;

final class ListenersLoader implements Loader {

    /**
     * @return void
     */
    public function onLoad(): void {
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
            $plugin = Zeno::getInstance();
            if (isset(class_implements($listener)[Listener::class])) {
                $plugin->getServer()->getPluginManager()->registerEvents($listener, $plugin);
            }
        }
        Zeno::getInstance()->getLogger()->notice("[Listener] " . count($listeners) . " listener(s) enregistré(s) !");
    }

    /**
     * @return void
     */
    public function onUnload(): void {}

}
