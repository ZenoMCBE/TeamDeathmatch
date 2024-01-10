<?php

namespace tdm\listeners;

use pocketmine\event\Listener;
use pocketmine\event\server\{DataPacketDecodeEvent, DataPacketSendEvent, QueryRegenerateEvent};
use pocketmine\network\mcpe\protocol\{LevelSoundEventPacket,
    OpenSignPacket,
    ProtocolInfo,
    SetTimePacket,
    StartGamePacket,
    types\LevelSoundEvent};
use pocketmine\world\World;
use tdm\managers\GameManager;
use tdm\TeamDeathmatch;

final class ServerListeners implements Listener {

    /**
     * @param DataPacketDecodeEvent $event
     * @return void
     */
    public function onDataPacketDecode(DataPacketDecodeEvent $event): void {
        // $origin = $event->getOrigin();
        $packetId = $event->getPacketId();
        $packetBuffer = $event->getPacketBuffer();
        if (
            strlen($packetBuffer) > 1492 &&
            $packetId != ProtocolInfo::LOGIN_PACKET
        ) {
            TeamDeathmatch::getInstance()->getLogger()->critical("Undecoded PacketID: " . $packetId . " (" . strlen($packetBuffer) . ")");
            // Server::getInstance()->getNetwork()->blockAddress($origin->getIp(), Limits::INT32_MAX);
            $event->cancel();
        }
    }

    /**
     * @param DataPacketSendEvent $event
     * @return void
     */
    public function onDataPacketSend(DataPacketSendEvent $event): void {
        $packets = $event->getPackets();
        foreach ($packets as $packet) {
            switch ($packet) {
                case $packet instanceof LevelSoundEventPacket:
                    if (
                        $packet->sound === LevelSoundEvent::ATTACK_NODAMAGE ||
                        $packet->sound === LevelSoundEvent::ATTACK_STRONG ||
                        $packet->sound === LevelSoundEvent::AMBIENT
                    ) {
                        $event->cancel();
                    }
                    break;
                case $packet instanceof OpenSignPacket:
                    $event->cancel();
                    break;
                case $packet instanceof StartGamePacket:
                    $packet->levelSettings->muteEmoteAnnouncements = true;
                    break;
                case $packet instanceof SetTimePacket:
                    $packet->time = World::TIME_NOON;
                    break;
            }
        }
    }

    /**
     * @param QueryRegenerateEvent $event
     * @return void
     */
    public function onQueryRegenerate(QueryRegenerateEvent $event): void {
        $gameApi = GameManager::getInstance();
        $maxPlayersToSet = $gameApi->getTeamPlayersLimit() * 2;
        $queryInfo = $event->getQueryInfo();
        $queryInfo->setMaxPlayerCount($maxPlayersToSet);
    }

}
