<?php

namespace zenogames\commands\player;

use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\Server;
use zenogames\librairies\commando\args\TargetArgument;
use zenogames\librairies\commando\BaseCommand;
use zenogames\librairies\commando\constraint\InGameRequiredConstraint;
use zenogames\librairies\commando\exception\ArgumentOrderException;
use zenogames\utils\Constants;
use zenogames\Zeno;

final class PingCommand extends BaseCommand {

    /**
     * CONSTRUCT
     */
    public function __construct() {
        parent::__construct(Zeno::getInstance(), "ping", "Connaître la latence d'un joueur", []);
        $this->setPermission(DefaultPermissions::ROOT_USER);
    }

    /**
     * @return void
     * @throws ArgumentOrderException
     */
    protected function prepare(): void {
        $this->addConstraint(new InGameRequiredConstraint($this));
        $this->registerArgument(0, new TargetArgument("joueur", true));
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
        $formatPing = function (int $ping): string {
            return match (true) {
                $ping > 201 => "§c$ping ms",
                $ping >= 101 && $ping < 200 => "§6$ping ms",
                default => "§a$ping ms",
            };
        };
        if (isset($args["joueur"])) {
            $target = Server::getInstance()->getPlayerByPrefix($args["joueur"]);
            if ($target instanceof Player) {
                $ping = $target->getNetworkSession()->getPing();
                $pingFormat = $formatPing($ping);
                $sender->sendMessage(Constants::PREFIX . "§fLe joueur §a{$target->getName()} §fpossède $pingFormat §f!");
            } else {
                $sender->sendMessage(Constants::PREFIX . "§cLe joueur {$args['joueur']} n'existe pas.");
            }
        } else {
            $pingSelf = $sender->getNetworkSession()->getPing();
            $pingSelfFormat = $formatPing($pingSelf);
            $sender->sendMessage(Constants::PREFIX . "§fVous possédez $pingSelfFormat §f!");
        }
    }

}
