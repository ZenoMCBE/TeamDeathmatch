<?php

namespace tdm\loaders\childs;

use tdm\loaders\Loader;
use tdm\managers\ProvidersManager;
use tdm\TeamDeathmatch;

final class ProvidersLoader implements Loader {

    /**
     * @return void
     */
    public function onLoad(): void {
        $providerApi = ProvidersManager::getInstance();
        $providerApi->loadProviders();
        TeamDeathmatch::getInstance()->getLogger()->notice("[Provider] " . $providerApi->getProviderCount() . " structure(s) de donnée(s) chargée(s) !");
    }

    /**
     * @return void
     */
    public function onUnload(): void {}

}
