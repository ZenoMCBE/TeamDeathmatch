<?php

namespace zenogames\tasks;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use zenogames\managers\GameManager;
use zenogames\utils\Constants;
use zenogames\utils\Utils;

final class RestartTask extends Task {

    /**
     * @var int
     */
    private int $time = 90;

    /**
     * @return void
     */
    public function onRun(): void {
        $gameApi = GameManager::getInstance();
        if ($gameApi->isEnded()) {
            if ($this->time > 0) {
                if (in_array($this->time, [60, 30, 15, 10, 5, 4, 3, 2, 1])) {
                    Server::getInstance()->broadcastMessage(Constants::PREFIX . "§fRéinitialisation de la partie dans §a" . ($this->time === 60 ? "1m" : $this->time . "s") . " §f!");
                    foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                        Utils::playSound($onlinePlayer, "note.pling");
                    }
                }
                $this->time--;
            } else {
                $gameApi->restart();
                $this->getHandler()?->cancel();
            }
        } else {
            $this->getHandler()?->cancel();
        }
    }

}
