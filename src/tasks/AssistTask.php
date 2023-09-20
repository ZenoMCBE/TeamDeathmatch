<?php

namespace zenogames\tasks;

use zenogames\managers\AssistManager;
use zenogames\managers\GameManager;
use zenogames\utils\Utils;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;

final class AssistTask extends Task {

    /**
     * @return void
     * @noinspection PhpDeprecationInspection
     */
    public function onRun(): void {
        $assistApi = AssistManager::getInstance();
        $assists = $assistApi->getAll();
        if (GameManager::getInstance()->isLaunched()) {
            if (!is_null($assists)) {
                foreach ($assists as $key => $value) {
                    $player = Server::getInstance()->getPlayerByPrefix(Utils::getPlayerName($key, false));
                    if ($player instanceof Player) {
                        $time = $value["time"];
                        if (!is_null($time)) {
                            if (($time - time()) <= 0) {
                                $assistApi->reinitialize($player);
                            }
                        }
                    }
                }
            }
        } else {
            $this->getHandler()?->cancel();
        }
    }

}
