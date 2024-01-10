<?php

namespace tdm\managers;

use tdm\utils\ids\ScoreboardTypeIds;
use tdm\utils\Utils;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

final class ScoreboardManager {

    use SingletonTrait;

    private const SEPARATOR = "\u{E000}";

    private const MINI_SEPARATOR = "\u{E141}";

    public const GLYPH_PER_LETTER = [
        'a' => "\u{E10D}",
        'b' => "\u{E10E}",
        'c' => "\u{E10F}",
        'd' => "\u{E110}",
        'e' => "\u{E111}",
        'f' => "\u{E112}",
        'g' => "\u{E113}",
        'h' => "\u{E114}",
        'i' => "\u{E115}",
        'j' => "\u{E116}",
        'k' => "\u{E117}",
        'l' => "\u{E118}",
        'm' => "\u{E119}",
        'n' => "\u{E11A}",
        'o' => "\u{E11B}",
        'p' => "\u{E11C}",
        'q' => "\u{E11D}",
        'r' => "\u{E11E}",
        's' => "\u{E11F}",
        't' => "\u{E120}",
        'u' => "\u{E121}",
        'v' => "\u{E122}",
        'w' => "\u{E123}",
        'x' => "\u{E124}",
        'y' => "\u{E125}",
        'z' => "\u{E126}",
    ];

    /**
     * @param Player $player
     * @param int $type
     * @return void
     * @noinspection PhpDeprecationInspection
     */
    public function sendScoreboard(Player $player, int $type): void {
        $gameApi = GameManager::getInstance();
        $rankApi = RankManager::getInstance();
        switch ($type) {
            case ScoreboardTypeIds::WAITING:
                $playersCount = count(Server::getInstance()->getOnlinePlayers());
                $playerRank = $rankApi->get($player);
                $playerRankCleanName = $rankApi->getRankColorByRank($playerRank) . $rankApi->getCleanRankNameByRank($playerRank);
                $playerTeam = $gameApi->hasPlayerTeam($player)
                    ? $gameApi->getFormattedColorNameByColorId($gameApi->getPlayerTeam($player))
                    : "§7Aucune";

                $this->changeTitle($player, $this->formatWordToGlyph("zeno"));
                $this->changeLine($player, 1, self::SEPARATOR);
                $this->changeLine($player, 2, " §l§q" . $player->getName());
                $this->changeLine($player, 3, " " . self::MINI_SEPARATOR . " §fGrade§7: " . $playerRankCleanName);
                $this->changeLine($player, 4, " " . self::MINI_SEPARATOR . " §fÉquipe§7: " . $playerTeam);
                $this->changeLine($player, 5, "  ");
                $this->changeLine($player, 6, " §l§qInfos");
                $this->changeLine($player, 7, " " . self::MINI_SEPARATOR . " §fMode§7: §aTDM");
                $this->changeLine($player, 8, " " . self::MINI_SEPARATOR . " §fJoueur(s)§7: §c" . $playersCount);
                $this->changeLine($player, 9, "§r" . self::SEPARATOR);
                break;
            case ScoreboardTypeIds::LAUNCH:
                $firstTeamPoints = $gameApi->getTeamPoints(1);
                $secondTeamPoints = $gameApi->getTeamPoints(2);
                $firstFormattedTeamName = $gameApi->getFormattedColorNameByColorId(1);
                $secondFormattedTeamName = $gameApi->getFormattedColorNameByColorId(2);

                $this->changeTitle($player, $this->formatWordToGlyph("zeno"));
                $this->changeLine($player, 1, self::SEPARATOR);
                $this->changeLine($player, 2, " §l§qInfos");
                $this->changeLine($player, 3, " " . self::MINI_SEPARATOR . " §fChrono§7: §a" . Utils::formatTime($gameApi->getRemainingTime()));
                $this->changeLine($player, 4, " " . self::MINI_SEPARATOR . " §fMode§7: §aTDM");
                $this->changeLine($player, 5, " ");
                $this->changeLine($player, 6, " §l§qPoints");
                $this->changeLine($player, 7, " " . self::MINI_SEPARATOR . " " . $firstFormattedTeamName . "§7: §a" . $firstTeamPoints);
                $this->changeLine($player, 8, " " . self::MINI_SEPARATOR . " " . $secondFormattedTeamName . "§7: §a" . $secondTeamPoints);
                $this->changeLine($player, 9, "§r" . self::SEPARATOR);
                break;
            case ScoreboardTypeIds::ENDED:
                $statsApi = StatsManager::getInstance();
                $winnerTeam = $gameApi->getWinnerTeam();
                if (!is_null($winnerTeam)) {
                    $winnerTeamColor = $gameApi->getTeamColor($winnerTeam);
                    $winnerTeamColorName = $gameApi->getColorNameByColorId($winnerTeamColor);
                    $winnerMinecraftTeamColor = $gameApi->getMinecraftColorByColorId($winnerTeamColor);
                    $formattedWinnerTeam = $winnerMinecraftTeamColor . $winnerTeamColorName;
                } else {
                    $formattedWinnerTeam = "§7Égalité";
                }
                $firstTeamColor = $gameApi->getTeamColor(1);
                $secondTeamColor = $gameApi->getTeamColor(2);
                $firstTeamPoints = $gameApi->getTeamPoints(1);
                $secondTeamPoints = $gameApi->getTeamPoints(2);
                $firstTeamMinecraftColor = $gameApi->getMinecraftColorByColorId($firstTeamColor);
                $secondTeamMinecraftColor = $gameApi->getMinecraftColorByColorId($secondTeamColor);
                $gameMvp = $statsApi->getScoreMvpAll();
                $gameMvpPlayer = Server::getInstance()->getPlayerByPrefix($gameMvp);
                if ($gameMvpPlayer instanceof Player) {
                    $gameMvpPlayerTeam = $gameApi->getPlayerTeam($gameMvpPlayer);
                    $gameMvpPlayerTeamColor = $gameApi->getTeamColor($gameMvpPlayerTeam);
                    $gameMvpPlayerTeamMinecraftColor = $gameApi->getMinecraftColorByColorId($gameMvpPlayerTeamColor);
                } else {
                    $gameMvpPlayerTeamMinecraftColor = "§7";
                }
                $teamMvp = $statsApi->getScoreMvpTeam($gameApi->getPlayerTeam($player));
                $playerTeam = $gameApi->getPlayerTeam($player);
                $playerFormattedMinecraftColor = $playerTeam === 1
                    ? $firstTeamMinecraftColor
                    : $secondTeamMinecraftColor;
                $this->changeTitle($player, $this->formatWordToGlyph("zeno"));
                $this->changeLine($player, 1, self::SEPARATOR);
                $this->changeLine($player, 2, " §l§qInfos");
                $this->changeLine($player, 3, " " . self::MINI_SEPARATOR . " §fGagnant§7: §6" . $formattedWinnerTeam);
                $this->changeLine($player, 4, " " . self::MINI_SEPARATOR . " §fPoints§7: " . $firstTeamMinecraftColor . $firstTeamPoints . "§8 | " . $secondTeamMinecraftColor . $secondTeamPoints);
                $this->changeLine($player, 5, " ");
                $this->changeLine($player, 6, " §l§qMVP");
                $this->changeLine($player, 7, " " . self::MINI_SEPARATOR . " §fP§7: " . $gameMvpPlayerTeamMinecraftColor . $gameMvp);
                $this->changeLine($player, 8, " " . self::MINI_SEPARATOR . " §fÉ§7: " . $playerFormattedMinecraftColor . $teamMvp);
                $this->changeLine($player, 9, "§r" . self::SEPARATOR);
                break;
        }
    }

    /**
     * @param bool $disconnect
     * @return void
     */
    public function updateOnlinePlayers(bool $disconnect): void {
        $onlinePlayers = Server::getInstance()->getOnlinePlayers();
        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $this->changeLine($onlinePlayer, 8, " " . self::MINI_SEPARATOR . " §fJoueur(s)§7: §c" . ($disconnect ? (count($onlinePlayers) - 1) : count($onlinePlayers)));
        }
    }

    /**
     * @param Player $player
     * @return void
     */
    public function updateRank(Player $player): void {
        $rankApi = RankManager::getInstance();
        $playerRank = $rankApi->get($player);
        $playerRankCleanName = $rankApi->getRankColorByRank($playerRank) . $rankApi->getCleanRankNameByRank($playerRank);
        $this->changeLine($player, 3, " " . self::MINI_SEPARATOR . " §fGrade§7: " . $playerRankCleanName);
    }

    /**
     * @param Player|null $player
     * @return void
     */
    public function updateTeam(?Player $player): void {
        $gameApi = GameManager::getInstance();
        if (!is_null($player)) {
            $playerTeam = $gameApi->hasPlayerTeam($player)
                ? $gameApi->getFormattedColorNameByColorId($gameApi->getPlayerTeam($player))
                : "§7Aucune";
            $this->changeLine($player, 4, " " . self::MINI_SEPARATOR . " §fÉquipe§7: " . $playerTeam);
        } else {
            foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                $playerTeam = $gameApi->hasPlayerTeam($onlinePlayer)
                    ? $gameApi->getFormattedColorNameByColorId($gameApi->getPlayerTeam($onlinePlayer))
                    : "§7Aucune";
                $this->changeLine($onlinePlayer, 4, " " . self::MINI_SEPARATOR . " §fÉquipe§7: " . $playerTeam);
            }
        }
    }

    /**
     * @param Player $player
     * @return void
     */
    public function updateLaunchScoreboard(Player $player): void {
        $gameApi = GameManager::getInstance();
        $firstTeamPoints = $gameApi->getTeamPoints(1);
        $secondTeamPoints = $gameApi->getTeamPoints(2);
        $firstFormattedTeamName = $gameApi->getFormattedColorNameByColorId(1);
        $secondFormattedTeamName = $gameApi->getFormattedColorNameByColorId(2);

        $this->changeTitle($player, $this->formatWordToGlyph("zeno"));
        $this->changeLine($player, 1, self::SEPARATOR);
        $this->changeLine($player, 2, " §l§qInfos");
        $this->changeLine($player, 3, " " . self::MINI_SEPARATOR . " §fChrono§7: §a" . Utils::formatTime($gameApi->getRemainingTime()));
        $this->changeLine($player, 4, " " . self::MINI_SEPARATOR . " §fMode§7: §aTDM");
        $this->changeLine($player, 5, " ");
        $this->changeLine($player, 6, " §l§qPoints");
        $this->changeLine($player, 7, " " . self::MINI_SEPARATOR . " " . $firstFormattedTeamName . "§7: §a" . $firstTeamPoints);
        $this->changeLine($player, 8, " " . self::MINI_SEPARATOR . " " . $secondFormattedTeamName . "§7: §a" . $secondTeamPoints);
        $this->changeLine($player, 9, "§r" . self::SEPARATOR);
    }

    /**
     * @param Player $player
     * @return void
     * @noinspection PhpUnused
     */
    public function removeScoreboard(Player $player): void {
        $packet = new RemoveObjectivePacket();
        $packet->objectiveName = "objective";
        $player->getNetworkSession()->sendDataPacket($packet);
    }

    /**
     * @param Player $player
     * @param string $title
     * @return void
     */
    private function changeTitle(Player $player, string $title): void {
        $packet = new SetDisplayObjectivePacket();
        $packet->displaySlot = "sidebar";
        $packet->objectiveName = "objective";
        $packet->displayName = $title;
        $packet->criteriaName = "dummy";
        $packet->sortOrder = 0;
        $player->getNetworkSession()->sendDataPacket($packet);
    }

    /**
     * @param Player $player
     * @param int $line
     * @param string $content
     * @return void
     */
    private function createLine(Player $player, int $line, string $content): void {
        $packetEntry = new ScorePacketEntry();
        $packetEntry->objectiveName = "objective";
        $packetEntry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
        $packetEntry->customName = $content;
        $packetEntry->score = $line;
        $packetEntry->scoreboardId = $line;
        $packetScore = new SetScorePacket();
        $packetScore->type = SetScorePacket::TYPE_CHANGE;
        $packetScore->entries[] = $packetEntry;
        $player->getNetworkSession()->sendDataPacket($packetScore);
    }

    /**
     * @param Player $player
     * @param int $line
     * @return void
     */
    private function removeLine(Player $player, int $line): void {
        $packetEntry = new ScorePacketEntry();
        $packetEntry->objectiveName = "objective";
        $packetEntry->score = $line;
        $packetEntry->scoreboardId = $line;
        $packetScore = new SetScorePacket();
        $packetScore->type = SetScorePacket::TYPE_REMOVE;
        $packetScore->entries[] = $packetEntry;
        $player->getNetworkSession()->sendDataPacket($packetScore);
    }

    /**
     * @param Player $player
     * @param int $line
     * @param string $content
     * @return void
     */
    private function changeLine(Player $player, int $line, string $content): void {
        $this->removeLine($player, $line);
        $this->createLine($player, $line, $content);
    }

    /**
     * @param string $word
     * @return string
     */
    public function formatWordToGlyph(string $word): string {
        return implode('', array_map(fn ($letter) => $this->getGlyphByLetter($letter), str_split($word)));
    }

    /**
     * @param string $letter
     * @return string
     */
    private function getGlyphByLetter(string $letter): string {
        return self::GLYPH_PER_LETTER[$letter] ?? "";
    }

}
