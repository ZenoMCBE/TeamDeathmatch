<?php

namespace zenogames\utils;

use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;

final class Utils {

    /**
     * @param string|Player $player
     * @param bool $upperName
     * @return string
     */
    public static function getPlayerName(string|Player $player, bool $upperName): string {
        $name = $player instanceof Player ? $player->getName() : $player;
        return $upperName ? str_replace(" ", "_", $name) : str_replace("_", " ", $name);
    }

    /**
     * @param Player $player
     * @param string $soundName
     * @return void
     */
    public static function playSound(Player $player, string $soundName): void {
        $position = $player->getPosition();
        $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create($soundName, $position->x, $position->y, $position->z, 50, 1));
    }

    /**
     * @param Player $player
     * @return void
     */
    public static function teleportToWaitingMap(Player $player): void {
        $spawnWorld = Server::getInstance()->getWorldManager()->getWorldByName(Constants::WAITING_MAP);
        if ($spawnWorld instanceof World) {
            $player->teleport(new Position(-0.5, 53, -0.5, $spawnWorld), 270, 0);
            self::prepare($player);
        }
    }

    /**
     * @param Player $player
     * @return void
     */
    public static function teleportToEndedMap(Player $player): void {
        $spawnWorld = Server::getInstance()->getWorldManager()->getWorldByName(Constants::ENDED_MAP);
        if ($spawnWorld instanceof World) {
            $player->teleport(new Position(96.5, 90, -5.5, $spawnWorld), 90, 0);
            self::prepare($player);
        }
    }

    /**
     * @param int $seconds
     * @return string
     */
    public static function formatTime(int $seconds): string {
        return gmdate("i:s", $seconds);
    }

    /**
     * @param Player $player
     * @return void
     */
    public static function prepare(Player $player): void {
        $player->setHealth($player->getMaxHealth());
        $player->getEffects()->clear();
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        $player->getOffHandInventory()->clearAll();
        $player->setGamemode(GameMode::SURVIVAL());
        $player->setAllowFlight(false);
        $player->setFlying(false);
        $player->getXpManager()->setXpLevel(0);
        $player->getXpManager()->setXpProgress(0.0);
        $player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());
        $player->getHungerManager()->setSaturation(20);
    }

}
