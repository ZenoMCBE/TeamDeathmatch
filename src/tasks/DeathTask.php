<?php

namespace tdm\tasks;

use tdm\managers\GameManager;
use tdm\managers\KitManager;
use tdm\utils\ids\KitIds;
use tdm\utils\Utils;
use pocketmine\entity\effect\{EffectInstance, VanillaEffects};
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\Limits;

final class DeathTask extends Task {

    /**
     * @var int
     */
    private int $time = 5;

    /**
     * @param Player $player
     */
    public function __construct(protected Player $player) {}

    /**
     * @return void
     */
    public function onRun(): void {
        if ($this->player->isConnected()) {
            if ($this->player->getEffects()->has(VanillaEffects::NIGHT_VISION())) {
                $this->player->getEffects()->remove(VanillaEffects::NIGHT_VISION());
            }
            $gameApi = GameManager::getInstance();
            if ($gameApi->isLaunched()) {
                if ($this->time <= 0) {
                    Utils::playSound($this->player, "note.flute");
                    $this->player->setNoClientPredictions(false);
                    if ($this->player->getEffects()->has(VanillaEffects::BLINDNESS())) {
                        $this->player->getEffects()->remove(VanillaEffects::BLINDNESS());
                    }
                    KitManager::getInstance()->send($this->player, KitIds::GAME);
                    $this->player->sendTitle("§l§q» §r§aVous êtes réapparu §l§q«", stay: 60);
                    $this->getHandler()?->cancel();
                } else {
                    Utils::playSound($this->player, "note.pling");
                    $this->player->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), Limits::INT32_MAX, 4, false));
                    $this->player->sendTitle("§l§4» §r§cVous êtes mort §l§4«", "§r§7Réapparition dans " . $this->time . "s");
                    $this->player->setNoClientPredictions();
                    $this->time--;
                }
            } else {
                $this->player->setNoClientPredictions(false);
                if ($this->player->getEffects()->has(VanillaEffects::BLINDNESS())) {
                    $this->player->getEffects()->remove(VanillaEffects::BLINDNESS());
                }
                foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                    $onlinePlayer->showPlayer($this->player);
                }
                Utils::teleportToEndedMap($this->player);
                $this->getHandler()?->cancel();
            }
        } else {
            $this->getHandler()?->cancel();
        }
    }

}
