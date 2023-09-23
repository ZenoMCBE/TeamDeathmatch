<?php

namespace zenogames\loaders\childs;

use zenogames\Zeno;
use zenogames\loaders\Loader;
use zenogames\utils\Constants;
use zenogames\utils\ids\MapIds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;

final class WorldsLoader implements Loader {

    /**
     * @return void
     */
    public function onLoad(): void {
        $worldsName = [
            Constants::WAITING_MAP,
            Constants::ENDED_MAP,
            MapIds::CARGO,
            MapIds::LEBRONZE,
            MapIds::MARZIPAN,
            MapIds::PADDINGTON,
            MapIds::REVOLUTION,
            MapIds::ULTRAVIOLET,
            MapIds::TOPAZ
        ];
        foreach ($worldsName as $worldName) {
            $worldManager = Server::getInstance()->getWorldManager();
            if (!$worldManager->isWorldLoaded($worldName)) {
                $worldManager->loadWorld($worldName, true);
            }
        }
        Zeno::getInstance()->getLogger()->notice("[World] " . count($worldsName) . " monde(s) chargé(s) !");
    }

    /**
     * @return void
     */
    public function onUnload(): void {
        $worldsName = [
            Constants::WAITING_MAP,
            Constants::ENDED_MAP,
            MapIds::CARGO,
            MapIds::LEBRONZE,
            MapIds::MARZIPAN,
            MapIds::PADDINGTON,
            MapIds::ULTRAVIOLET,
            MapIds::TOPAZ
        ];
        foreach ($worldsName as $worldName) {
            $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
            if ($world instanceof World) {
                foreach ($world->getEntities() as $entity) {
                    if (!$entity instanceof Player) {
                        $entity->close();
                    }
                }
            }
        }
        Zeno::getInstance()->getLogger()->notice("[World] " . count($worldsName) . " monde(s) déchargé(s) !");
    }

}
