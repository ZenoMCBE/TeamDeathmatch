<?php

namespace tdm\managers;

use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use tdm\forms\VoteForms;
use tdm\tasks\VoteTask;
use tdm\utils\{Constants, Utils};
use tdm\TeamDeathmatch;

final class VoteManager {

    use SingletonTrait;

    /**
     * @var bool
     */
    private bool $active = false;

    /**
     * @var array
     */
    private array $votes = [];

    /**
     * @var array
     */
    private array $playerVote = [];

    /**
     * @return void
     */
    public function start(): void {
        $mapApi = MapManager::getInstance();
        $firstMap = $mapApi->getRandomMap();
        $secondMap = $mapApi->getRandomMap([$firstMap]);
        $thirdMap = $mapApi->getRandomMap([$firstMap, $secondMap]);
        foreach ([$firstMap, $secondMap, $thirdMap] as $maps) {
            $this->votes[$maps] = 0;
        }
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $playerName = Utils::getPlayerName($player, true);
            $this->playerVote[$playerName] = null;
            $player->getInventory()->setItem(1, VanillaItems::PAPER()->setCustomName("§r§l§q» §r§aVote de map §l§q«"));
            $player->sendForm(VoteForms::getInstance()->getMainForm());
            Utils::playSound($player, "mob.enderdragon.hit");
        }
        $this->setActive(true);
        Server::getInstance()->broadcastMessage(Constants::PREFIX . "§fUn vote de map vient d'être lancé ! Vous avez §a1 minute §fpour voter parmis ces maps suivantes : §a" . implode(", ", array_map('ucfirst', $this->getMaps())) . " §f!");
        TeamDeathmatch::getInstance()->getScheduler()->scheduleRepeatingTask(new VoteTask(), 20);
    }

    /**
     * @return void
     */
    public function end(): void {
        $server = Server::getInstance();
        $gameApi = GameManager::getInstance();
        $mostVotedMap = $this->getMostVotedMap();
        $cleanMostVotedMapName = ucfirst($mostVotedMap);
        $gameApi->setMap($mostVotedMap);
        $votersByMap = array_fill_keys($this->getMaps(), []);
        foreach ($this->playerVote as $player => $votedMap) {
            if (!is_null($votedMap)) {
                $votersByMap[$votedMap][] = $player;
            }
        }
        $server->broadcastMessage("§r§l§q» §r§aRésultats des votes §l§q«");
        foreach ($this->getTop() as $map => $vote) {
            $mapVoters = $votersByMap[$map];
            $formattedVoters = !empty($mapVoters) ? "§8[§7" . implode(", ", $mapVoters) . "§8]" : "";
            $server->broadcastMessage("§l§q| §r§f" . ucfirst($map) . " §8(§7" . $vote . "§8) " . $formattedVoters);
        }
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $playerInventory = $player->getInventory();
            if ($playerInventory->contains(VanillaItems::PAPER())) {
                $playerInventory->remove(VanillaItems::PAPER());
            }
            $player->sendTitle("§r§l§q» §r§a" . $cleanMostVotedMapName . " §l§q«");
            $player->sendSubTitle("§7La map " . $cleanMostVotedMapName . " a obtenue " . $this->getMapVote($mostVotedMap) . " vote(s) !");
        }
        $this->setActive(false);
        $this->reset();
    }

    /**
     * @return void
     */
    public function reset(): void {
        $this->playerVote = [];
        $this->votes = [];
    }

    /**
     * @return bool
     */
    public function isActive(): bool {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return void
     */
    public function setActive(bool $active): void {
        $this->active = $active;
    }

    /**
     * @param Player $player
     * @return string|null
     */
    public function getVote(Player $player): ?string {
        $playerName = Utils::getPlayerName($player, true);
        return $this->playerVote[$playerName];
    }

    /**
     * @param Player $player
     * @param string $map
     * @return void
     */
    public function setVote(Player $player, string $map): void {
        $playerName = Utils::getPlayerName($player, true);
        $this->playerVote[$playerName] = $map;
        $this->incrementMapVote($map);
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function hasVoted(Player $player): bool {
        return !is_null($this->getVote($player));
    }

    /**
     * @param string $map
     * @return void
     */
    public function incrementMapVote(string $map): void {
        $this->votes[$map]++;
    }

    /**
     * @param string $map
     * @return int
     */
    public function getMapVote(string $map): int {
        return intval($this->votes[$map]);
    }

    /**
     * @return string
     */
    public function getMostVotedMap(): string {
        return array_search(max($this->votes), $this->votes);
    }

    /**
     * @return array
     */
    public function getTop(): array {
        $votes = $this->votes;
        arsort($votes);
        return $votes;
    }

    /**
     * @return array
     */
    public function getMaps(): array {
        return array_keys($this->votes);
    }

}
