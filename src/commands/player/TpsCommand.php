<?php

namespace zenogames\commands\player;

use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\Server;
use zenogames\librairies\commando\BaseCommand;
use zenogames\Zeno;

final class TpsCommand extends BaseCommand {

    /**
     * CONSTRUCT
     */
    public function __construct() {
        parent::__construct(Zeno::getInstance(), "tps", "Visualiser le TPS du serveur", []);
        $this->setPermission(DefaultPermissions::ROOT_USER);
    }

    /**
     * @return void
     */
    protected function prepare(): void {}

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        $server = Server::getInstance();

        $formatTickPerSecond = fn ($value) => match (true) {
            $value < 12 => "§c$value tick(s)",
            $value < 17 => "§6$value tick(s)",
            default => "§a$value tick(s)",
        };

        $formatTickUsage = fn ($value) => match (true) {
            $value > 80 => "§c$value%",
            $value > 50 => "§6$value%",
            default => "§a$value%",
        };

        $tickPerSecond = $formatTickPerSecond($server->getTicksPerSecond());
        $tickUsage = $formatTickUsage($server->getTickUsage());
        $tickPerSecondAverage = $formatTickPerSecond($server->getTicksPerSecondAverage());
        $tickUsageAverage = $formatTickUsage($server->getTickUsageAverage());

        $sender->sendMessage("§l§q» §r§aPerformance du serveur §l§q«§r");
        $sender->sendMessage("§l§q| §r§aTPS §8- $tickPerSecond");
        $sender->sendMessage("§l§q| §r§aTU §8- $tickUsage");
        $sender->sendMessage("§l§q| §r§aTPS/A §8- $tickPerSecondAverage");
        $sender->sendMessage("§l§q| §r§aTU/A §8- $tickUsageAverage");
    }

}
