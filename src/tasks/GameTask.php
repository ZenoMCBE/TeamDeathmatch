<?php

namespace tdm\tasks;

use tdm\managers\{GameManager, ScoreboardManager};
use tdm\utils\ids\ScoreboardTypeIds;
use pocketmine\scheduler\Task;
use pocketmine\Server;

final class GameTask extends Task {

    /**
     * @return void
     */
    public function onRun(): void {
        $gameApi = GameManager::getInstance();
        $scoreboardApi = ScoreboardManager::getInstance();
        $onlinePlayers = Server::getInstance()->getOnlinePlayers();
        if ($gameApi->isLaunched()) {
            foreach ($onlinePlayers as $player) {
                $scoreboardApi->updateLaunchScoreboard($player);
            }
            $gameApi->decrementTime();
            if ($gameApi->isTimeUp()) {
                $firstTeamPoints = $gameApi->getTeamPoints(1);
                $secondTeamPoints = $gameApi->getTeamPoints(2);
                $winnerTeam = ($firstTeamPoints > $secondTeamPoints) ? 1 : (($secondTeamPoints > $firstTeamPoints) ? 2 : null);
                $gameApi->end($winnerTeam);
                $this->getHandler()?->cancel();
            }
        } else {
            foreach ($onlinePlayers as $player) {
                $scoreboardApi->sendScoreboard($player, ScoreboardTypeIds::ENDED);
            }
            $this->getHandler()?->cancel();
        }
    }

}
