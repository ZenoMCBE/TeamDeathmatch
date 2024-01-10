<?php

namespace tdm\commands\staff;

use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\Server;
use tdm\librairies\commando\args\OptionArgument;
use tdm\librairies\commando\args\TargetArgument;
use tdm\librairies\commando\BaseCommand;
use tdm\librairies\commando\constraint\InGameRequiredConstraint;
use tdm\librairies\commando\exception\ArgumentOrderException;
use tdm\managers\{KitManager, RankManager, ScoreboardManager};
use tdm\TeamDeathmatch;
use tdm\utils\{Constants, ids\KitIds, Utils};

final class SetRankCommand extends BaseCommand {

    /**
     * CONSTRUCT
     */
    public function __construct() {
        parent::__construct(TeamDeathmatch::getInstance(), "setrank", "Définir un grade à un joueur", []);
        $this->setPermission(DefaultPermissions::ROOT_USER);
    }

    /**
     * @return void
     * @throws ArgumentOrderException
     */
    protected function prepare(): void {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new TargetArgument("joueur"));
        $this->registerArgument(1, new OptionArgument("grade", RankManager::getInstance()->getRanks()));
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @return void
     * @noinspection PhpDeprecationInspection
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        assert($sender instanceof Player);
        $rankApi = RankManager::getInstance();
        if ($rankApi->hasPermission($sender, 2)) {
            if (isset($args["joueur"], $args["grade"])) {
                $targetName = Utils::getPlayerName($args["joueur"], true);
                if ($rankApi->exist($targetName)) {
                    if ($rankApi->isValidRank($args["grade"])) {
                        $rankApi->set($targetName, strtolower($args["grade"]));
                        $targetRealName = Utils::getPlayerName($targetName, false);
                        Server::getInstance()->broadcastMessage(Constants::PREFIX . "§c§k!§r§6§k!§r§e§k!§r§a§k!§r§9§k!§r §a" . $targetRealName . " §fvient de recevoir le grade §a" . $rankApi->getCleanRankNameByRank($args["grade"]) . " §f! §9§k!§r§a§k!§r§e§k!§r§6§k!§r§c§k!");
                        $target = Server::getInstance()->getPlayerByPrefix($targetName);
                        if ($target instanceof Player) {
                            ScoreboardManager::getInstance()->updateRank($target);
                            KitManager::getInstance()->send($target, KitIds::WAITING);
                        }
                    } else {
                        $sender->sendMessage(Constants::PREFIX . "§cLe grade " . ucfirst($args["grade"]) . " n'existe pas.");
                    }
                } else {
                    $sender->sendMessage(Constants::PREFIX . "§cLe joueur " . $args["joueur"] . " n'existe pas.");
                }
            } else {
                $sender->sendMessage(Constants::PREFIX . "§fVous devez faire §a/setrank (joueur) (grade) §fpour définir un grade à un joueur");
            }
        } else {
            $sender->sendMessage(Constants::PREFIX . "§cVous ne disposez pas des permissions nécessaires pour utiliser cette commande.");
        }
    }

}
