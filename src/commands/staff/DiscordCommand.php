<?php

namespace zenogames\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\Server;
use zenogames\librairies\commando\args\IntegerArgument;
use zenogames\librairies\commando\args\TargetArgument;
use zenogames\librairies\commando\BaseCommand;
use zenogames\librairies\commando\constraint\InGameRequiredConstraint;
use zenogames\librairies\commando\exception\ArgumentOrderException;
use zenogames\managers\RankManager;
use zenogames\managers\WebApiManager;
use zenogames\utils\Constants;
use zenogames\TeamDeathmatch;

final class DiscordCommand extends BaseCommand {

    /**
     * CONSTRUCT
     */
    public function __construct() {
        parent::__construct(TeamDeathmatch::getInstance(), "discord", "Relier le compte d'un joueur à son Discord", []);
        $this->setPermission(DefaultPermissions::ROOT_USER);
    }

    /**
     * @return void
     * @throws ArgumentOrderException
     */
    protected function prepare(): void {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new TargetArgument("joueur"));
        $this->registerArgument(1, new IntegerArgument("id"));
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        assert($sender instanceof Player);
        if (RankManager::getInstance()->hasPermission($sender, 2)) {
            if (isset($args["joueur"], $args["id"])) {
                $target = Server::getInstance()->getPlayerByPrefix($args["joueur"]);
                if ($target instanceof Player) {
                    if (is_int($args["id"])) {
                        WebApiManager::getInstance()->addDiscordUser($target->getName(), $args["id"]);
                        $sender->sendMessage(Constants::PREFIX . "§fVous venez de relier le joueur §a" . $target->getName() . " §fau compte Discord appartenant à l'ID §a" . $args["id"] . " §f!");
                    } else {
                        $sender->sendMessage(Constants::PREFIX . "§cL'ID " . $args["id"] . " est invalide.");
                    }
                } else {
                    $sender->sendMessage(Constants::PREFIX . "§cLe joueur " . $args["joueur"] . " n'existe pas.");
                }
            } else {
                $sender->sendMessage(Constants::PREFIX . "§fVous devez faire §a/discord (joueur) (id) §fpour relier le compte d'un joueur à son Discord !");
            }
        } else {
            $sender->sendMessage(Constants::PREFIX . "§cVous n'avez pas la permission d'utiliser cette commande.");
        }
    }

}
