<?php

namespace zenogames\managers;

use JsonException;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use zenogames\datas\DataCache;
use zenogames\datas\DefaultDataCache;
use zenogames\utils\ids\RankIds;
use zenogames\utils\Utils;
use zenogames\Zeno;

final class RankManager implements DataCache, DefaultDataCache {

    use SingletonTrait;

    /**
     * @var array
     */
    private array $cache = [];

    /**
     * @return void
     */
    public function loadCache(): void {
        $providerData = $this->getProvider()->getAll();
        foreach ($providerData as $key => $value) {
            $this->cache[$key] = $value;
        }
    }

    /**
     * @return array
     */
    public function getCache(): array {
        return $this->cache;
    }

    /**
     * @param Player $player
     * @return void
     */
    public function setDefaultData(Player $player): void {
        if (!$this->exist($player)) {
            $playerName = Utils::getPlayerName($player, true);
            $this->cache[$playerName] = $this->getDefaultData();
        }
    }

    /**
     * @param string|Player $player
     * @return bool
     */
    public function exist(string|Player $player): bool {
        $playerName = Utils::getPlayerName($player, true);
        return array_key_exists($playerName, $this->cache);
    }


    /**
     * @param string|Player $player
     * @return string
     */
    public function get(string|Player $player): string {
        $playerName = Utils::getPlayerName($player, true);
        return strval($this->cache[$playerName]);
    }

    /**
     * @param string|Player $player
     * @param string $rank
     * @return void
     */
    public function set(string|Player $player, string $rank): void {
        $playerName = Utils::getPlayerName($player, true);
        $this->cache[$playerName] = $rank;
    }

    /**
     * @param Player $player
     * @param string $message
     * @return string
     */
    public function formatChatMessage(Player $player, string $message): string {
        $gameApi = GameManager::getInstance();
        $leagueApi = Zeno::getInstance()->getStatsApi()->getLeagueManager();
        $playerRank = $this->get($player);
        if ($gameApi->hasPlayerTeam($player)) {
            $formattedLeague = !$gameApi->isLaunched() ? "§8[" . $leagueApi->formatLeague($player) . "§8]§r" : "";
            $team = $gameApi->getPlayerTeam($player);
            $teamColor = $gameApi->getTeamColor($team);
            $colorName = $gameApi->getColorNameByColorId($teamColor);
            $minecraftColor = $gameApi->getMinecraftColorByColorId($teamColor);
            return str_replace(
                ["{LEAGUE}", "{COLOR}", "{TEAM}", "{PLAYER}", "{MSG}"],
                [$formattedLeague, $minecraftColor, $colorName, $player->getName(), TextFormat::clean($message)],
                $formattedLeague . "§8[{COLOR}{TEAM}§8]" . $this->getChatFormatByRank($playerRank)
            );
        } else {
            $formattedLeague = !$gameApi->isLaunched() ? "§8[" . $leagueApi->formatLeague($player) . "§8]§r" . (Server::getInstance()->isOp($player->getName()) ? "" : " ") : "";
            return str_replace(
                ["{LEAGUE}", "{COLOR}", "{PLAYER}", "{MSG}"],
                [$formattedLeague, $this->getRankColorByRank($playerRank), $player->getName(), TextFormat::clean($message)],
                $formattedLeague . $this->getChatFormatByRank($playerRank)
            );
        }
    }

    /**
     * @param string $rank
     * @return string
     */
    public function getChatFormatByRank(string $rank): string {
        return match ($rank) {
            RankIds::PLAYER => " {COLOR}{PLAYER} §l§8» §r§7{MSG}",
            RankIds::HOSTER => "§8[§sHoster§8] {COLOR}{PLAYER} §l§8» §r§7{MSG}",
            RankIds::ADMIN => "§8[§cAdmin§8] {COLOR}{PLAYER} §l§8» §r§7{MSG}"
        };
    }

    /**
     * @param string $rank
     * @return string
     */
    public function getRankColorByRank(string $rank): string {
        return match ($rank) {
            RankIds::PLAYER => "§7",
            RankIds::HOSTER => "§s",
            RankIds::ADMIN => "§c"
        };
    }

    /**
     * @param string $rank
     * @return string
     */
    public function getCleanRankNameByRank(string $rank): string {
        return match ($rank) {
            RankIds::PLAYER => "Joueur",
            RankIds::HOSTER => "Hoster",
            RankIds::ADMIN => "Admin"
        };
    }

    /**
     * @param string $rank
     * @return int
     */
    public function getPermission(string $rank): int {
        return match ($rank) {
            RankIds::PLAYER => 0,
            RankIds::HOSTER => 1,
            RankIds::ADMIN => 2
        };
    }

    /**
     * @param Player $player
     * @param int $permission
     * @return bool
     */
    public function hasPermission(Player $player, int $permission): bool {
        $playerRank = $this->get($player);
        return $this->getPermission($playerRank) >= $permission || Server::getInstance()->isOp($player->getName());
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function isHoster(Player $player): bool {
        $playerRank = $this->get($player);
        return $this->getPermission($playerRank) >= 1 || Server::getInstance()->isOp($player->getName());
    }

    /**
     * @return string
     */
    public function getDefaultData(): string {
        return RankIds::PLAYER;
    }

    /**
     * @return void
     * @throws JsonException
     */
    public function unloadCache(): void {
        $provider = $this->getProvider();
        $provider->setAll([]);
        foreach ($this->getCache() as $key => $value) {
            $provider->set($key, $value);
        }
        $provider->save();
    }

    /**
     * @param string $rank
     * @return bool
     */
    public function isValidRank(string $rank): bool {
        return in_array($rank, $this->getRanks());
    }

    /**
     * @return array
     */
    public function getRanks(): array {
        return RankIds::ALL_RANKS;
    }

    /**
     * @return Config
     */
    public function getProvider(): Config {
        return ProvidersManager::getInstance()->getProvider("Rank");
    }

}
