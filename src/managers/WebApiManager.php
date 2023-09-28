<?php

namespace zenogames\managers;

use pocketmine\utils\SingletonTrait;
use zenogames\utils\Constants;
use zenogames\Zeno;

final class WebApiManager {

    use SingletonTrait;

    /**
     * @param array $teamMembers
     * @param string $teamName
     * @return void
     */
    public function sendTeamToServer(array $teamMembers, string $teamName): void {
        if ($teamName == "teamOne" || $teamName == "teamTwo") {
            $data = [$teamName => $teamMembers];
            $jsonData = json_encode($data);
            $url = Constants::WEB_API_URL . str_replace("{team}", ucfirst($teamName), Constants::WEB_ADD_PLAYER_TEAM_ENDPOINT);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            $response = curl_exec($ch);
            $logger = Zeno::getInstance()->getLogger();
            if (curl_errno($ch)) {
                $logger->notice("Erreur curl : " . curl_error($ch));
            } else {
                $logger->notice("Réponse du serveur : " . $response);
            }
            curl_close($ch);
        }
    }

    /**
     * @param string $username
     * @param int $idDiscord
     * @return void
     */
    public function addDiscordUser(string $username, int $idDiscord): void {
        $logger = Zeno::getInstance()->getLogger();
        $encodedUsername = urlencode($username);
        $url = Constants::WEB_API_URL . str_replace(["{name}", "{id}"], [$encodedUsername, $idDiscord], Constants::WEB_ADD_PLAYER_ENDPOINT);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $logger->notice("Erreur curl : " . curl_error($ch));
        } else {
            $logger->notice("Réponse du serveur : " . $response);
        }
        curl_close($ch);
    }

    /**
     * @param int $maxPlayers
     * @return void
     */
    public function setMaxPlayers(int $maxPlayers): void {
        $logger = Zeno::getInstance()->getLogger();
        $url = Constants::WEB_API_URL . str_replace("{count}", $maxPlayers, Constants::WEB_SET_MAX_PLAYERS_ENDPOINT);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $logger->notice("Erreur curl : " . curl_error($ch));
        } else {
            $logger->notice("Réponse du serveur : " . $response);
        }
        curl_close($ch);
    }

    /**
     * @param string $status
     * @return void
     */
    public function setGameStatus(string $status): void {
        if (
            $status == 'true' ||
            $status == 'false' ||
            $status == 'ended'
        ) {
            $logger = Zeno::getInstance()->getLogger();
            $url = Constants::WEB_API_URL . str_replace("{status}", $status, Constants::WEB_GAME_STATUS_ENDPOINT);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $logger->notice("Erreur curl : " . curl_error($ch));
            } else {
                $logger->notice("Réponse du serveur : " . $response);
            }
            curl_close($ch);
        }
    }

    /**
     * @return void
     */
    public function clearGame(): void {
        $logger = Zeno::getInstance()->getLogger();
        $url = Constants::WEB_API_URL . Constants::WEB_RESET_GAME_ENDPOINT;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $logger->notice("Erreur curl : " . curl_error($ch));
        } else {
            $logger->notice("Réponse du serveur : " . $response);
        }
        curl_close($ch);
    }

}
