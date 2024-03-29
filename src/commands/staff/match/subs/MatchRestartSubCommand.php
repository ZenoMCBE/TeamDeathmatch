<?php

namespace tdm\commands\staff;

namespace tdm\commands\staff\match\subs;

use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use tdm\librairies\commando\BaseSubCommand;
use tdm\librairies\commando\constraint\InGameRequiredConstraint;
use tdm\managers\GameManager;
use tdm\managers\RankManager;
use tdm\TeamDeathmatch;
use tdm\utils\Constants;

final class MatchRestartSubCommand extends BaseSubCommand {

    /**
     * CONSTRUCT
     */
    public function __construct() {
        parent::__construct(TeamDeathmatch::getInstance(), "restart", "Réinitialiser la partie", []);
        $this->setPermission(DefaultPermissions::ROOT_USER);
    }

    /**
     * @return void
     */
    protected function prepare(): void {
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        assert($sender instanceof Player);
        if (RankManager::getInstance()->isHoster($sender)) {
            $gameApi = GameManager::getInstance();
            if ($gameApi->isEnded()) {
                $gameApi->restart();
            } else {
                $sender->sendMessage(Constants::PREFIX . "§cLa partie n'est pas terminée.");
            }
        } else {
            $sender->sendMessage(Constants::PREFIX . "§cVous n'avez pas la permission d'utiliser cette commande.");
        }
    }

}
