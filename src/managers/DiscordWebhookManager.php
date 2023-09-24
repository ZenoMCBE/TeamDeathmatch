<?php

namespace zenogames\managers;

use pocketmine\utils\SingletonTrait;
use zenogames\librairies\discordwebhookapi\Embed;
use zenogames\librairies\discordwebhookapi\Message;
use zenogames\librairies\discordwebhookapi\Webhook;
use zenogames\utils\ids\ColorIds;
use zenogames\utils\ids\WebhookIds;
use zenogames\utils\Utils;
use zenogames\Zeno;

final class DiscordWebhookManager {

    use SingletonTrait;

    /**
     * @param string $gameId
     * @param string $map
     * @param int|null $winnerTeam
     * @param array $teamColors
     * @param array $resultElos
     * @return void
     */
    public function sendMatchSummary(string $gameId, string $map, ?int $winnerTeam, array $teamColors, array $resultElos): void {
        date_default_timezone_set('Europe/Paris');

        $gameApi = GameManager::getInstance();

        $webhook = new Webhook(WebhookIds::MATCH_SUMMARY);
        $message = new Message();
        $embed = new Embed();

        $message->setUsername("Résumé du match");
        $message->setAvatarURL("https://cdn.discordapp.com/attachments/1082834006162284615/1091804806403854386/A0193870-0E0D-4919-A98B-3CB157CAA111.jpg");

        $embed->setTitle("Partie terminée・Team Deathmatch");
        $embed->setDescription("**ID**: " . $gameId . "\n**Gagnant**: " . (!is_null($winnerTeam) ? ucfirst(($gameApi->getColorNameByColorId($gameApi->getTeamColor($winnerTeam)))) : "Égalité")  . " (**" . $gameApi->getTeamPoints(1) . "**・**" . $gameApi->getTeamPoints(2) . "**)\n**Map**: " . ucfirst($map));
        $embed->setColor(!is_null($winnerTeam) ? $this->getHexColorByColorId($gameApi->getTeamColor($winnerTeam)) : 0xAAAAAA);

        for ($i = 0; $i <= 1; $i++) {
            $fieldContent = "";
            foreach ($resultElos[$i] as $player => $resultElo) {
                $playerElo = Zeno::getInstance()->getStatsApi()->getEloManager()->get($player);
                $fieldContent .= Utils::getPlayerName($player, false) . " → " . $playerElo . " (" . $resultElos[$i][$player] . ")\n";
            }
            $embed->addField(ucfirst($teamColors[$i]) . " (" . $gameApi->getAverageTeamLeague($i + 1) . ")", $fieldContent);
        }

        $embed->setFooter("Zeno Ranked・" . date('d/m/Y à H:i:s'), "https://cdn.discordapp.com/attachments/1082834006162284615/1091804806403854386/A0193870-0E0D-4919-A98B-3CB157CAA111.jpg");

        $message->addEmbed($embed);
        $webhook->send($message);
    }

    /**
     * @param string $gameId
     * @param string $map
     * @param int|null $winnerTeam
     * @param array $teamColors
     * @param array $stats
     * @return void
     */
    public function sendStatsSummary(string $gameId, string $map, ?int $winnerTeam, array $teamColors, array $stats): void {
        date_default_timezone_set('Europe/Paris');

        $gameApi = GameManager::getInstance();

        $webhook = new Webhook(WebhookIds::STATS_SUMMARY);
        $message = new Message();
        $embed = new Embed();

        $message->setUsername("Résumé des statistiques");
        $message->setAvatarURL("https://cdn.discordapp.com/attachments/1082834006162284615/1091804806403854386/A0193870-0E0D-4919-A98B-3CB157CAA111.jpg");

        $embed->setTitle("Partie terminée・Team Deathmatch");
        $embed->setDescription("**ID**: " . $gameId . "\n**Gagnant**: " . (!is_null($winnerTeam) ? ucfirst(($gameApi->getColorNameByColorId($gameApi->getTeamColor($winnerTeam)))) : "Égalité")  . " (**" . $gameApi->getTeamPoints(1) . "**・**" . $gameApi->getTeamPoints(2) . "**)\n**Map**: " . ucfirst($map));
        $embed->setColor(!is_null($winnerTeam) ? $this->getHexColorByColorId($gameApi->getTeamColor($winnerTeam)) : 0xAAAAAA);

        $sortedScore = [];
        $individualStats = [];

        for ($i = 0; $i <= 1; $i++) {
            $fieldContent = "";
            foreach ($stats[$i] as $player => $statList) {
                $playerStatsValues = array_values($statList);
                $sortedScore[$player] = StatsManager::getInstance()->getPlayerScore($player);
                $individualStats[$player] = $playerStatsValues;
            }
            arsort($sortedScore);
            foreach ($sortedScore as $player => $score) {
                $fieldContent .= Utils::getPlayerName($player, false) . " → " . $score . " (" . implode(" | ", $individualStats[$player]) . ")\n";
            }
            $embed->addField(ucfirst($teamColors[$i]) . " [" . GameManager::getInstance()->getAverageTeamLeague($i + 1) . "]", $fieldContent);
        }

        $embed->setFooter("Zeno Ranked・" . date('d/m/Y à H:i:s'), "https://cdn.discordapp.com/attachments/1082834006162284615/1091804806403854386/A0193870-0E0D-4919-A98B-3CB157CAA111.jpg");

        $message->addEmbed($embed);
        $webhook->send($message);
    }

    /**
     * @param string $colorId
     * @return int
     */
    public function getHexColorByColorId(string $colorId): int {
        return match ($colorId) {
            ColorIds::RED => 0xFF5555,
            ColorIds::ORANGE => 0xFFAA00,
            ColorIds::YELLOW => 0xFFFF55,
            ColorIds::LIME => 0x55FF55,
            ColorIds::AQUA => 0x55FFFF,
            ColorIds::BLUE => 0x5555FF,
            ColorIds::PURPLE => 0xAA00AA,
            ColorIds::PINK => 0xFF55FF,
            ColorIds::WHITE => 0xFFFFFF,
            ColorIds::BLACK => 0x555555
        };
    }

}
