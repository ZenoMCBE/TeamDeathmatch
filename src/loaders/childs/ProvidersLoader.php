<?php

namespace zenogames\loaders\childs;

use zenogames\loaders\Loader;
use zenogames\managers\ProvidersManager;
use zenogames\Zeno;

final class ProvidersLoader implements Loader {

    /**
     * @return void
     */
    public function onLoad(): void {
        $providerApi = ProvidersManager::getInstance();
        $providerApi->loadProviders();
        Zeno::getInstance()->getLogger()->notice("[Provider] " . $providerApi->getProviderCount() . " structure(s) de donnée(s) chargée(s) !");
    }

    /**
     * @return void
     */
    public function onUnload(): void {}

}
