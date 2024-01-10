<?php

namespace tdm\managers;

use tdm\TeamDeathmatch;
use tdm\loaders\childs\{DatasLoader,
    GameLoader,
    CommandsLoader,
    EnchantmentsLoader,
    HooksLoader,
    ListenersLoader,
    ProvidersLoader,
    WorldsLoader};
use tdm\loaders\Loader;
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
