<?php

namespace zenogames\commands\staff;

use zenogames\managers\RankManager;
use zenogames\Zeno;
use zenogames\librairies\commando\BaseCommand;
use zenogames\librairies\commando\constraint\InGameRequiredConstraint;
use zenogames\utils\Constants;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\Server;

final class ExactCoordsCommand extends BaseCommand {

    /**
     * CONSTRUCT
     */
    public function __construct() {
        parent::__construct(Zeno::getInstance(), "exactcoords", "Connaître sa position exacte", []);
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
    public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
        assert($sender instanceof Player);
        if (RankManager::getInstance()->hasPermission($sender, 2)) {
            $location = $sender->getLocation();
            $coordinates = [
                "X" => round($location->getX(), 2),
                "Y" => round($location->getY(), 2),
                "Z" => round($location->getZ(), 2),
                "YAW" => round($location->getYaw(), 2),
                "PITCH" => round($location->getPitch(), 2),
            ];
            $coordinatesFormat = implode(" | ", array_map(fn ($key, $value) => "$key: $value", array_keys($coordinates), $coordinates));
            $sender->sendMessage(Constants::PREFIX . "§fVoici votre position exacte : §a$coordinatesFormat §f!");
        } else {
            $sender->sendMessage(Constants::PREFIX . "§cVous n'avez pas la permission d'utiliser cette commande.");
        }
    }

}
