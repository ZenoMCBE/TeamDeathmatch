<?php

namespace tdm\commands\staff\match;

use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use tdm\commands\staff\match\subs\MatchRestartSubCommand;
use tdm\commands\staff\match\subs\MatchStopSubCommand;
use tdm\librairies\commando\BaseCommand;
use tdm\librairies\commando\constraint\InGameRequiredConstraint;
use tdm\managers\RankManager;
use tdm\TeamDeathmatch;
use tdm\utils\Constants;

final class MatchCommand extends BaseCommand {

    /**
     * CONSTRUCT
     */
    public function __construct() {
        parent::__construct(TeamDeathmatch::getInstance(), "match", "Gérer le match", []);
        $this->setPermission(DefaultPermissions::ROOT_USER);
    }

    /**
     * @return void
     */
    protected function prepare(): void {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerSubCommand(new MatchRestartSubCommand());
        $this->registerSubCommand(new MatchStopSubCommand());
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
            $sender->sendMessage("§l§q» §r§aCommandes de gestion de la partie §l§q«§r");
            $sender->sendMessage("§l§q| §r§a/match restart §8- §fRéinitialiser la partie");
            $sender->sendMessage("§l§q| §r§a/match stop §8- §fArrêter de force la partie");
        } else {
            $sender->sendMessage(Constants::PREFIX . "§cVous n'avez pas la permission d'utiliser cette commande.");
        }
    }

}
