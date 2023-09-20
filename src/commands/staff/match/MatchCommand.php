<?php

namespace zenogames\commands\staff\match;

use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\Server;
use zenogames\commands\staff\match\subs\MatchRestartSubCommand;
use zenogames\commands\staff\match\subs\MatchStopSubCommand;
use zenogames\librairies\commando\BaseCommand;
use zenogames\librairies\commando\constraint\InGameRequiredConstraint;
use zenogames\utils\Constants;
use zenogames\Zeno;

final class MatchCommand extends BaseCommand {

    /**
     * CONSTRUCT
     */
    public function __construct() {
        parent::__construct(Zeno::getInstance(), "match", "Gérer le match", []);
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
        if (Server::getInstance()->isOp($sender->getName())) {
            $sender->sendMessage("§l§q» §r§aCommandes de gestion de la partie §l§q«§r");
            $sender->sendMessage("§l§q| §r§a/match restart §8- §fRéinitialiser la partie");
            $sender->sendMessage("§l§q| §r§a/match stop §8- §fArrêter de force la partie");
        } else {
            $sender->sendMessage(Constants::PREFIX . "§cVous n'avez pas la permission d'utiliser cette commande.");
        }
    }

}
