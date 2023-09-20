<?php

namespace zenogames\managers;

use zenogames\utils\Utils;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

final class AssistManager {

    use SingletonTrait;

    /**
     * @var array
     */
    private array $assists = [];

    /**
     * @param Player $player
     * @return void
     */
    public function create(Player $player): void {
        if (!$this->exist($player)) {
            $playerName = Utils::getPlayerName($player, true);
            $this->assists[$playerName] = [
                "time" => null,
                "players" => []
            ];
        }
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function exist(Player $player): bool {
        $playerName = Utils::getPlayerName($player, true);
        return array_key_exists($playerName, $this->assists);
    }

    /**
     * @param Player $player
     * @return int
     */
    public function getTime(Player $player): int {
        $playerName = Utils::getPlayerName($player, true);
        return intval($this->assists[$playerName]["time"]);
    }

    /**
     * @param Player $player
     * @return array
     */
    public function getPlayers(Player $player): array {
        $playerName = Utils::getPlayerName($player, true);
        return $this->assists[$playerName]["players"];
    }

    /**
     * @param Player $player
     * @param Player $killer
     * @return array
     */
    public function getAssistersWithoutKiller(Player $player, Player $killer): array {
        $killerName = Utils::getPlayerName($killer, true);
        $assisters = $this->getPlayers($player);
        if (in_array($killerName, $assisters)) {
            unset($assisters[array_search($killerName, $assisters)]);
        }
        return $assisters;
    }

    /**
     * @param Player $player
     * @param Player $attacker
     * @return void
     */
    public function update(Player $player, Player $attacker): void {
        $playerName = Utils::getPlayerName($player, true);
        $attackerName = Utils::getPlayerName($attacker, true);
        $this->assists[$playerName]["time"] = time() + 10;
        if (!in_array($attackerName, $this->assists[$playerName]["players"])) {
            $this->assists[$playerName]["players"][] = $attackerName;
        }
    }

    /**
     * @param Player $player
     * @return void
     */
    public function reinitialize(Player $player): void {
        if ($this->exist($player)) {
            $playerName = Utils::getPlayerName($player, true);
            $this->assists[$playerName]["players"] = [];
        }
    }

    /**
     * @return void
     */
    public function reset(): void {
        $this->assists = [];
    }

    /**
     * @return array|null
     */
    public function getAll(): ?array {
        return !empty($this->assists) ? $this->assists : null;
    }

}
