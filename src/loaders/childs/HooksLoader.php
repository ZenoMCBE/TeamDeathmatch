<?php

namespace tdm\loaders\childs;

use ReflectionException;
use tdm\managers\CustomItemManager;
use tdm\TeamDeathmatch;
use tdm\librairies\commando\exception\HookAlreadyRegistered;
use tdm\librairies\commando\PacketHooker;
use tdm\librairies\invmenu\InvMenuHandler;
use tdm\loaders\Loader;

final class HooksLoader implements Loader {

    /**
     * @return void
     * @throws HookAlreadyRegistered
     * @throws ReflectionException
     */
    public function onLoad(): void {
        $plugin = TeamDeathmatch::getInstance();
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
