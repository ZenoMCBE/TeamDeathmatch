<?php

namespace zenogames\managers;

use zenogames\loaders\childs\DatasLoader;
use zenogames\loaders\childs\GameLoader;
use zenogames\TeamDeathmatch;
use zenogames\loaders\childs\CommandsLoader;
use zenogames\loaders\childs\EnchantmentsLoader;
use zenogames\loaders\childs\HooksLoader;
use zenogames\loaders\childs\ListenersLoader;
use zenogames\loaders\childs\ProvidersLoader;
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
            new GameLoader(),
            new HooksLoader(),
            new ListenersLoader(),
            new ProvidersLoader(),
            new WorldsLoader(),
            new DatasLoader()
        ];
        foreach ($loaders as $loader) {
            if (isset(class_implements($loader)[Loader::class])) {
                $loader->onLoad();
            }
        }
        TeamDeathmatch::getInstance()->getLogger()->notice("[Loader] " . count($loaders) ." loader(s) chargé(s) !");
    }

    /**
     * @return void
     */
    public function unloadAll(): void {
        $loaders = [
            new GameLoader(),
            new WorldsLoader(),
            new DatasLoader()
        ];
        foreach ($loaders as $loader) {
            if (isset(class_implements($loader)[Loader::class])) {
                $loader->onUnload();
            }
        }
        TeamDeathmatch::getInstance()->getLogger()->notice("[Loader] " . count($loaders) ." loader(s) déchargé(s) !");
    }

}
