<?php

namespace zenogames\loaders\childs;

use zenogames\loaders\Loader;
use zenogames\managers\GameManager;
use zenogames\managers\WebApiManager;
use zenogames\utils\ids\GameStatusIds;
use zenogames\TeamDeathmatch;

final class GameLoader implements Loader {

    /**
     * @return void
     */
    public function onLoad(): void {
        GameManager::getInstance()->loadDefaultParameters();
        TeamDeathmatch::getInstance()->getLogger()->notice("[Game] Paramètre(s) par défaut chargées !");
    }

    /**
     * @return void
     */
    public function onUnload(): void {
        $webApi = WebApiManager::getInstance();
        $webApi->setGameStatus(GameStatusIds::ENDED);
        $webApi->clearGame();
        TeamDeathmatch::getInstance()->getLogger()->notice("[Game] Paramètre(s) par défaut chargées !");
    }

}
