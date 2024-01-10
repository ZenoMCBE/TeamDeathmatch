<?php

namespace tdm\managers;

use pocketmine\utils\SingletonTrait;
use tdm\TeamDeathmatch;
use tdm\utils\Constants;

final class WebApiManager {

    use SingletonTrait;

    /**
     * @param array $teamsData
     * @return void
     */
    public function sendTeamToServer(array $teamsData): void {
        $logger = TeamDeathmatch::getInstance()->getLogger();
        foreach ($teamsData as $teamName => $players) {
            $data = [
                "teamMembers" => $players
            ];
            $serverUrl = Constants::WEB_API_URL . str_replace("{team}", ucfirst($teamName),Constants::WEB_ADD_PLAYER_TEAM_ENDPOINT);
            $jsonData = json_encode($data);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $serverUrl);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            $response = curl_exec($curl);
            if (curl_errno($curl)) {
                $logger->notice("Erreur curl : " . curl_error($curl));
            } else {
                $logger->notice("Réponse du serveur : " . $response);
            }
            curl_close($curl);
        }
    }

    /**
     * @param string $username
     * @param int $idDiscord
     * @return void
     */
    public function addDiscordUser(string $username, int $idDiscord): void {
        $logger = TeamDeathmatch::getInstance()->getLogger();
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
        $logger = TeamDeathmatch::getInstance()->getLogger();
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
            $logger = TeamDeathmatch::getInstance()->getLogger();
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
        $logger = TeamDeathmatch::getInstance()->getLogger();
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
