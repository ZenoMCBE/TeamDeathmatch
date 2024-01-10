<?php

namespace tdm\loaders\childs;

use JsonException;
use tdm\datas\DataCache;
use tdm\loaders\Loader;
use tdm\managers\RankManager;
use tdm\TeamDeathmatch;

final class DatasLoader implements Loader {

    /**
     * @return void
     */
    public function onLoad(): void {
        $classes = [
            RankManager::getInstance(),
        ];
        foreach ($classes as $class) {
            if (isset(class_implements($class)[DataCache::class])) {
                $class->loadCache();
            }
        }
        TeamDeathmatch::getInstance()->getLogger()->notice("[Data] " . count($classes) . " fichier(s) de donnée(s) chargé(s) !");
    }

    /**
     * @return void
     * @throws JsonException
     */
    public function onUnload(): void {
        $classes = [
            RankManager::getInstance(),
        ];
        foreach ($classes as $class) {
            if (isset(class_implements($class)[DataCache::class])) {
                $class->unloadCache();
            }
        }
        TeamDeathmatch::getInstance()->getLogger()->notice("[Data] " . count($classes) . " fichier(s) de donnée(s) déchargé(s) !");
    }

}
