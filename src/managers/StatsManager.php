<?php

namespace zenogames\managers;

use zenogames\utils\ids\StatsIds;
use zenogames\utils\Utils;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

final class StatsManager {

    use SingletonTrait;

    /**
     * @var array
     */
    public array $stats = [];

    /**
     * @var array
     */
    public array $scores = [];

    /**
     * @param Player $player
     * @return void
     */
    public function create(Player $player): void {
        if (!$this->exist($player)) {
            $playerName = Utils::getPlayerName($player, true);
            $this->stats[$playerName] = [
                StatsIds::KILL => 0,
                StatsIds::ASSIST => 0,
                StatsIds::DEATH => 0,
                StatsIds::VOID_DEATH => 0,
                StatsIds::KILLSTREAK => 0,
                StatsIds::BEST_KILLSTREAK => 0,
                StatsIds::ARROW_SHOT => 0,
                StatsIds::ARROW_HIT => 0,
                StatsIds::ARROW_BOOST => 0,
                StatsIds::DAMAGE_DEALED => 0,
                StatsIds::DAMAGE_TAKEN => 0,
                StatsIds::GOLDEN_APPLE_EATEN => 0,
                StatsIds::CRIT => 0
            ];
        }
    }

    /**
     * @param string|Player $player
     * @return bool
     */
    public function exist(string|Player $player): bool {
        $playerName = Utils::getPlayerName($player, true);
        return array_key_exists($playerName, $this->stats);
    }

    /**
     * @return void
     */
    public function reset(): void {
        $this->stats = [];
        $this->scores = [];
    }

    /**
     * @param string|Player $player
     * @param string $stats
     * @return int
     */
    public function get(string|Player $player, string $stats): int {
        $playerName = Utils::getPlayerName($player, true);
        return intval($this->stats[$playerName][$stats]);
    }

    /**
     * @param string|Player $player
     * @return array
     */
    public function getAll(string|Player $player): array {
        $playerName = Utils::getPlayerName($player, true);
        return $this->stats[$playerName];
    }

    /**
     * @param string|Player $player
     * @param string $stats
     * @param int $amount
     * @return void
     */
    public function add(string|Player $player, string $stats, int $amount = 1): void {
        $playerName = Utils::getPlayerName($player, true);
        $this->stats[$playerName][$stats] += $amount;
    }


    /**
     * @param string|Player $player
     * @param string $stats
     * @param int $amount
     * @return void
     */
    public function set(string|Player $player, string $stats, int $amount): void {
        $playerName = Utils::getPlayerName($player, true);
        $this->stats[$playerName][$stats] = $amount;
    }

    /**
     * @param string|Player $player
     * @return void
     */
    public function delete(string|Player $player): void {
        if ($this->exist($player)) {
            $playerName = Utils::getPlayerName($player, true);
            unset($this->stats[$playerName]);
        }
    }

    /**
     * @param string|Player $player
     * @return int|float
     */
    public function calculateKdr(string|Player $player): int|float {
        $playerName = Utils::getPlayerName($player, true);
        $kill = max(0, $this->get($playerName, StatsIds::KILL));
        $death = max(0, $this->get($playerName, StatsIds::DEATH));
        return $kill > 0 && $death > 0
            ? round($kill / $death, 2)
            : 0;
    }

    /**
     * @param string|Player $player
     * @return int|float
     */
    public function calculateKadr(string|Player $player): int|float {
        $playerName = Utils::getPlayerName($player, true);
        $kill = max(0, $this->get($playerName, StatsIds::KILL));
        $assist = max(0, $this->get($playerName, StatsIds::ASSIST));
        $death = max(0, $this->get($playerName, StatsIds::DEATH));
        return $kill > 0 && $assist > 0 && $death > 0
            ? round(($kill + $assist) / $death, 2)
            : 0;
    }

    /**
     * @param string|Player $player
     * @return int
     */
    public function calculateArrowHitByArrowShotPercentage(string|Player $player): int {
        $playerName = Utils::getPlayerName($player, true);
        $arrowShot = max(0, $this->get($playerName, StatsIds::ARROW_SHOT));
        $arrowHit = max(0, $this->get($playerName, StatsIds::ARROW_HIT));
        return $arrowShot > 0 || $arrowHit > 0
            ? intval(($arrowHit / $arrowShot) * 100)
            : 0;
    }

    /*public function getIndividualPlayersTeamScore(int $team): array {
        $scores = [];
        $gameApi = GameManager::getInstance();
        foreach ($this->scores as $player => $score) {
            $playerTeam = $gameApi->getPlayerTeam($player);
            if ($playerTeam === $team) {
                $scores[$player] = $score;
            }
        }
        ksort($scores);
        return $scores;
    }*/

    /**
     * @param int $team
     * @return array
     */
    public function getIndividualPlayersTeamStats(int $team): array {
        $individualStats = [];
        $gameApi = GameManager::getInstance();
        foreach ($this->stats as $player => $stats) {
            $playerTeam = $gameApi->getPlayerTeam($player);
            if ($playerTeam === $team) {
                foreach ($stats as $stat => $amount) {
                    if (in_array($stat, [StatsIds::KILL, StatsIds::ASSIST, StatsIds::DEATH, StatsIds::KILLSTREAK, StatsIds::BEST_KILLSTREAK, StatsIds::DAMAGE_DEALED])) {
                        $individualStats[$player][$stat] = $amount;
                    }
                }
            }
        }
        ksort($individualStats);
        return $individualStats;
    }

    /**
     * @param int $team
     * @return int
     */
    public function calculateAverageTeamScore(int $team): int {
        $score = 0;
        $gameApi = GameManager::getInstance();
        foreach ($this->scores as $player => $score) {
            $playerTeam = $gameApi->getPlayerTeam($player);
            if ($playerTeam === $team) {
                $score += $score;
            }
        }
        return intval($score / $gameApi->countTeamPlayers($team)) ?? 0;
    }

    /**
     * @param Player $player
     * @return void
     */
    public function showStatsPopup(Player $player): void {
        $kill = $this->get($player, StatsIds::KILL);
        $assist = $this->get($player, StatsIds::ASSIST);
        $death = $this->get($player, StatsIds::DEATH);
        $killstreak = $this->get($player, StatsIds::KILLSTREAK);
        $bestKillstreak = $this->get($player, StatsIds::BEST_KILLSTREAK);

        $player->sendPopup("§r§l§8» §r§fK§7: §a" . $kill . " §8| §fA§7: §b" . $assist . " §8| §fD§7: §c" . $death . " §8| §fKS§7: §a" . $killstreak . " §8| §fBKS§7: §a" . $bestKillstreak . " §l§8«");
    }

    /**
     * @param string $statsId
     * @return array
     */
    public function getTopStats(string $statsId): array {
        $array = [];
        foreach ($this->stats as $player => $stat) {
            $array[$player] = $stat[$statsId];
        }
        arsort($array);
        return $array;
    }

    /**
     * @return void
     */
    public function generatePlayersScore(): void {
        foreach ($this->stats as $player => $stat) {
            $finalScore = 0;
            foreach ($stat as $statsId => $value) {
                $score = $this->getScoreByStats($statsId);
                if (!is_null($score)) {
                    $finalScore += $value * $score;
                }
            }
            $finalScore = max($finalScore, 0);
            $this->scores[$player] = intval($finalScore);
        }
    }

    /**
     * @param string|Player $player
     * @return int
     */
    public function getPlayerScore(string|Player $player): int {
        $playerName = Utils::getPlayerName($player, true);
        return intval($this->scores[$playerName]);
    }

    /**
     * @return array
     */
    public function getTopScore(): array {
        $leaderboardScore = $this->scores;
        arsort($leaderboardScore);
        return $leaderboardScore;
    }

    /**
     * @param Player $player
     * @return void
     * @noinspection PhpDeprecationInspection
     */
    public function showScoreMessage(Player $player): void {
        $gameApi = GameManager::getInstance();
        $leaderboard = $this->getTopScore();
        $player->sendMessage("§r§l§q» §r§aTableau des scores §l§q«");
        foreach ($leaderboard as $playerName => $score) {
            $playerName = Utils::getPlayerName($playerName, true);
            if ($gameApi->hasPlayerTeam($playerName)) {
                $playerTeam = $gameApi->getPlayerTeam($playerName);
                $playerTeamColor = $gameApi->getTeamColor($playerTeam);
                $playerTeamMinecraftTeamColor = $gameApi->getMinecraftColorByColorId($playerTeamColor);
                $kill = $this->get($playerName, StatsIds::KILL);
                $assist = $this->get($playerName, StatsIds::ASSIST);
                $death = $this->get($playerName, StatsIds::DEATH);
                $killstreak = $this->get($playerName, StatsIds::KILLSTREAK);
                $bestKillstreak = $this->get($playerName, StatsIds::BEST_KILLSTREAK);
                $damageDealed = $this->get($playerName, StatsIds::DAMAGE_DEALED);
                $formattedStats = "§8[§a" . $kill . " §8| §b" . $assist . " §8| §c" . $death . " §8| §a" . $killstreak . " §8| §a" . $bestKillstreak . " §8| §e" . $damageDealed . "§8]";
                $player->sendMessage("§l§q| §r" . $playerTeamMinecraftTeamColor . Utils::getPlayerName($playerName, false) . " §8(§7" . $this->getPlayerScore($playerName) . "§8) " . $formattedStats);
            }
        }
    }

    /**
     * @return string
     * @noinspection PhpDeprecationInspection
     */
    public function getScoreMvpAll(): string {
        $mvpPlayerName = array_key_first($this->getTopScore());
        $player = Server::getInstance()->getPlayerByPrefix(Utils::getPlayerName($mvpPlayerName, false));
        return $player instanceof Player ? $player->getName() : "§7???";
    }

    /**
     * @param int $team
     * @return string
     * @noinspection PhpDeprecationInspection
     */
    public function getScoreMvpTeam(int $team): string {
        $gameApi = GameManager::getInstance();
        foreach ($this->getTopScore() as $key => $value) {
            $player = Server::getInstance()->getPlayerByPrefix(Utils::getPlayerName($key, false));
            if ($player instanceof Player) {
                if ($gameApi->getPlayerTeam($player) === $team) {
                    return $player->getName();
                }
            }
        }
        return "§7???";
    }

    /**
     * @param string $stats
     * @return int|float|null
     */
    public function getScoreByStats(string $stats): int|float|null {
        return match ($stats) {
            StatsIds::KILL, StatsIds::BEST_KILLSTREAK => 10,
            StatsIds::ASSIST, StatsIds::KILLSTREAK => 5,
            StatsIds::ARROW_HIT, StatsIds::CRIT => 2,
            StatsIds::DAMAGE_DEALED => 1.5,
            default => null
        };
    }

    /**
     * @param string $stats
     * @return string
     */
    public function getStatsNameByStats(string $stats): string {
        return match ($stats) {
            StatsIds::KILL => "Kill(s)",
            StatsIds::ASSIST => "Assistance(s)",
            StatsIds::DEATH => "Mort(s)",
            StatsIds::VOID_DEATH => "Mort(s) dans le vide",
            StatsIds::KILLSTREAK => "Série de kill(s) actuel",
            StatsIds::BEST_KILLSTREAK => "Meilleure série de kill(s)",
            StatsIds::ARROW_SHOT => "Flèche(s) tirée(s)",
            StatsIds::ARROW_HIT => "Flèche(s) touchée(s)",
            StatsIds::ARROW_BOOST => "Boost(s) à l'arc",
            StatsIds::DAMAGE_DEALED => "Dégât(s) infligé(s)",
            StatsIds::DAMAGE_TAKEN => "Dégât(s) subit(s)",
            StatsIds::GOLDEN_APPLE_EATEN => "Gapple(s) mangée(s)",
            StatsIds::CRIT => "Coup(s) critique(s)"
        };
    }

    /**
     * @param string $stats
     * @return bool
     */
    public function isValidStats(string $stats): bool {
        return in_array($stats, $this->getAllStats());
    }

    /**
     * @return array
     */
    public function getAllStats(): array {
        return StatsIds::ALL_STATS;
    }

}
