<?php

namespace zenogames\loaders\childs;

use ReflectionException;
use zenogames\managers\CustomItemManager;
use zenogames\Zeno;
use zenogames\librairies\commando\exception\HookAlreadyRegistered;
use zenogames\librairies\commando\PacketHooker;
use zenogames\librairies\invmenu\InvMenuHandler;
use zenogames\loaders\Loader;

final class HooksLoader implements Loader {

    /**
     * @return void
     * @throws HookAlreadyRegistered
     * @throws ReflectionException
     */
    public function onLoad() : void {
        $plugin = Zeno::getInstance();
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($plugin);
        }
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($plugin);
        }
        CustomItemManager::getInstance()->registerAll();
    }

    /**
     * @return void
     */
    public function onUnload(): void {}

}
