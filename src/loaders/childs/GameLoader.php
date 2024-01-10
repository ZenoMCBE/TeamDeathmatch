<?php

namespace tdm\loaders\childs;

use tdm\loaders\Loader;
use tdm\managers\GameManager;
use tdm\TeamDeathmatch;

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
        /* $webApi = WebApiManager::getInstance();
        $webApi->setGameStatus(GameStatusIds::ENDED);
        $webApi->clearGame(); */
        TeamDeathmatch::getInstance()->getLogger()->notice("[Game] Paramètre(s) par défaut chargées !");
    }

}
