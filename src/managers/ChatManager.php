<?php

namespace tdm\managers;

use pocketmine\player\Player;
use pocketmine\utils\{SingletonTrait, TextFormat};
use tdm\utils\Utils;

final class ChatManager {

    use SingletonTrait;

    /**
     * @var array
     */
    private array $data = [
        "cooldown" => [],
        "message" => []
    ];

    /**
     * @param Player $player
     * @return bool
     */
    public function hasSavedMessage(Player $player): bool {
        $playerName = Utils::getPlayerName($player, true);
        return isset($this->data["message"][$playerName]);
    }

    /**
     * @param Player $player
     * @param string $message
     * @return bool
     */
    public function isSameMessage(Player $player, string $message): bool {
        return $this->getSavedMessage($player) == TextFormat::clean($message);
    }

    /**
     * @param Player $player
     * @param string $message
     * @return void
     */
    public function setMessageToSave(Player $player, string $message): void {
        $playerName = Utils::getPlayerName($player, true);
        $this->data["message"][$playerName] = TextFormat::clean($message);
    }

    /**
     * @param Player $player
     * @return string|null
     */
    public function getSavedMessage(Player $player): ?string {
        $playerName = Utils::getPlayerName($player, true);
        return $this->hasSavedMessage($player) ? $this->data["message"][$playerName] : null;
    }

    /**
     * @param Player $player
     * @param int $time
     * @return void
     */
    public function addAntiSpam(Player $player, int $time): void {
        $playerName = Utils::getPlayerName($player, true);
        $this->data["cooldown"][$playerName] = time() + $time;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function isInAntiSpam(Player $player): bool {
        $playerName = Utils::getPlayerName($player, true);
        return isset($this->data["cooldown"][$playerName]) && intval($this->data["cooldown"][$playerName]) > time();
    }

    /**
     * @param string $message
     * @return bool
     */
    public function isEmpty(string $message): bool {
        return empty(substr($message, 2));
    }

}
