<?php

namespace tdm\tasks;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use tdm\managers\{GameManager, VoteManager};
use tdm\utils\{Constants, Utils};

final class VoteTask extends Task {

    /**
     * @var int
     */
    private int $time = 60;

    /**
     * @return void
     */
    public function onRun(): void {
        if (GameManager::getInstance()->isWaiting()) {
            if ($this->time > 0) {
                if (in_array($this->time, [30, 15, 10, 5, 4, 3, 2, 1])) {
                    Server::getInstance()->broadcastMessage(Constants::PREFIX . "§fFin du vote dans §a" . $this->time . "s §f!");
                    foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                        Utils::playSound($onlinePlayer, "note.pling");
                    }
                }
                $this->time--;
            } else {
                VoteManager::getInstance()->end();
                $this->getHandler()?->cancel();
            }
        } else {
            $this->getHandler()?->cancel();
        }
    }

}
