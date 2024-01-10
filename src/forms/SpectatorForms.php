<?php

namespace tdm\forms;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;
use tdm\librairies\formapi\SimpleForm;
use tdm\managers\GameManager;
use tdm\utils\Constants;
use tdm\utils\Utils;

final class SpectatorForms {

    use SingletonTrait;

    /**
     * @return SimpleForm
     * @noinspection PhpDeprecationInspection
     */
    public function getTeleportForm(): SimpleForm {
        $gameApi = GameManager::getInstance();
        $form = new SimpleForm(function (Player $player, ?string $data = null) use ($gameApi) {
            if (is_string($data)) {
                if ($gameApi->isLaunched()) {
                    $targetName = Utils::getPlayerName($data, false);
                    $target = Server::getInstance()->getPlayerByPrefix($targetName);
                    if ($target instanceof Player) {
                        $targetLocation = $target->getLocation();
                        $player->teleport(Position::fromObject($targetLocation->asVector3(), $target->getWorld()), $targetLocation->getYaw(), $targetLocation->getPitch());
                        $player->sendMessage(Constants::PREFIX . "§fVous venez de vous téléporter sur §a" . $target->getName() . " §f!");
                    } else {
                        $player->sendMessage(Constants::PREFIX . "§cLe joueur " . $targetName . " n'est plus dans la partie.");
                    }
                } else {
                    $player->sendMessage(Constants::PREFIX . "§cVous ne pouvez pas vous téléporter à un joueur car la partie est terminée.");
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aTéléporteur §l§q«");
        $form->setContent(Constants::PREFIX . "§fBienvenue dans le §amenu du téléporteur §f! Veuillez cliquer  !");
        $gamePlayers = $gameApi->getAllPlayersInGame();
        foreach ($gamePlayers as $gamePlayer) {
            $playerName = Utils::getPlayerName($gamePlayer, false);
            $team = $gameApi->getPlayerTeam($gamePlayer);
            $teamColor = $gameApi->getTeamColor($team);
            $minecraftColorTeam = $gameApi->getMinecraftColorByColorId($teamColor);
            $form->addButton($minecraftColorTeam . $playerName, label: $playerName);
        }
        return $form;
    }

}
