<?php

namespace zenogames\managers;

use zenogames\Zeno;
use zenogames\loaders\childs\CommandsLoader;
use zenogames\loaders\childs\EnchantmentsLoader;
use zenogames\loaders\childs\HooksLoader;
use zenogames\loaders\childs\ListenersLoader;
use zenogames\loaders\childs\WorldsLoader;
use zenogames\loaders\Loader;
use pocketmine\utils\SingletonTrait;

final class LoadersManager {

    use SingletonTrait;

    /**
     * @return void
     */
    public function loadAll(): void {
        $loaders = [
            new CommandsLoader(),
            new EnchantmentsLoader(),
            new HooksLoader(),
            new ListenersLoader(),
            new WorldsLoader()
        ];
        foreach ($loaders as $loader) {
            if (isset(class_implements($loader)[Loader::class])) {
                $loader->onLoad();
            }
        }
        Zeno::getInstance()->getLogger()->notice("[Loader] " . count($loaders) ." loader(s) chargé(s) !");
    }

    /**
     * @return void
     */
    public function unloadAll(): void {
        $loaders = [
            new WorldsLoader()
        ];
        foreach ($loaders as $loader) {
            if (isset(class_implements($loader)[Loader::class])) {
                $loader->onUnload();
            }
        }
        Zeno::getInstance()->getLogger()->notice("[Loader] " . count($loaders) ." loader(s) déchargé(s) !");
    }

}
