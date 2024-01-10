<?php

namespace tdm\utils;

use pocketmine\entity\animation\HurtAnimation;
use pocketmine\entity\projectile\Projectile;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\{GameMode, Player};
use pocketmine\Server;
use pocketmine\world\{Position, World};

final class Utils {

    /**
     * @param Player $player
     * @param Player $damager
     * @param Projectile $projectile
     * @return void
     */
    public static function simulateProjectileHit(Player $player, Player $damager, Projectile $projectile): void {
        NetworkBroadcastUtils::broadcastPackets(array_unique(array_merge($player->getViewers(), $damager->getViewers(), [$player, $damager])), (new HurtAnimation($player))->encode());
        $projectileMotion = $projectile->getMotion();
        $player->knockBack($projectileMotion->x, $projectileMotion->z, verticalLimit: 0.5);
    }

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

        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $onlinePlayer->showPlayer($player);
        }
    }

}
