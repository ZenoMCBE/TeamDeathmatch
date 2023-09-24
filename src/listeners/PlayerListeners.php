<?php

namespace zenogames\listeners;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\item\enchantment\ItemFlags;
use zenogames\forms\GameManagementForm;
use zenogames\forms\MatchSummaryForm;
use zenogames\managers\AssistManager;
use zenogames\managers\ChatManager;
use zenogames\managers\GameManager;
use zenogames\managers\KitManager;
use zenogames\managers\MapManager;
use zenogames\managers\RankManager;
use zenogames\managers\ScoreboardManager;
use zenogames\managers\StatsManager;
use zenogames\utils\Constants;
use zenogames\utils\ids\KitIds;
use zenogames\utils\ids\ScoreboardTypeIds;
use zenogames\utils\ids\StatsIds;
use zenogames\utils\Utils;
use pocketmine\block\FenceGate;
use pocketmine\block\Trapdoor;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemEnchantEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMissSwingEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleSwimEvent;
use pocketmine\item\GoldenApple;
use pocketmine\item\ItemTypeIds;
use pocketmine\player\chat\LegacyRawChatFormatter;
use pocketmine\Server;
use zenogames\Zeno;

final class PlayerListeners implements Listener {

    /**
     * @param PlayerPreLoginEvent $event
     * @return void
     */
    public function onPreLogin(PlayerPreLoginEvent $event): void {
        $playerInfo = $event->getPlayerInfo();
        $server = Server::getInstance();
        $gameApi = GameManager::getInstance();
        $playerName = $playerInfo->getUsername();
        if ($server->getNetwork()->getValidConnectionCount() > $server->getQueryInformation()->getMaxPlayerCount()) {
            $event->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_FULL, "§l§q» §r§aLa partie est pleine §l§q«");
        }
        if (!$server->isWhitelisted($playerName)) {
            $event->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_WHITELISTED, "§l§q» §r§aServeur sous liste blanche §l§q«");
        }
        if (
            ($gameApi->isLaunched() && !$gameApi->hasPlayerTeam($playerName)) ||
            $gameApi->isEnded()
        ) {
            $event->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_PLUGIN, "§l§q» §r§aUne partie est déjà en cours §l§q«");
        }
    }

    /**
     * @param PlayerLoginEvent $event
     * @return void
     */
    public function onLogin(PlayerLoginEvent $event): void {
        $player = $event->getPlayer();
        $kitApi = KitManager::getInstance();
        $gameApi = GameManager::getInstance();
        $rankApi = RankManager::getInstance();
        $rankApi->setDefaultData($player);
        switch ($gameApi->getStatus()) {
            case $gameApi::WAITING_STATUS:
                Utils::teleportToWaitingMap($player);
                $kitApi->send($player, KitIds::WAITING);
                break;
            case $gameApi::LAUNCH_STATUS:
                if ($gameApi->hasPlayerTeam($player)) {
                    MapManager::getInstance()->teleportToTeamSpawn($player);
                    $kitApi->send($player, KitIds::GAME);
                } else {
                    // TODO: Système de spec
                    $event->cancel();
                }
                break;
            case $gameApi::END_STATUS:
                $event->cancel();
                break;
        }
    }

    /**
     * @param PlayerJoinEvent $event
     * @return void
     */
    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $assistApi = AssistManager::getInstance();
        $gameApi = GameManager::getInstance();
        $scoreboardApi = ScoreboardManager::getInstance();
        $statsApi = StatsManager::getInstance();
        $permanentStatsApi = Zeno::getInstance()->getStatsApi();
        $permanentStatsApi->getEloManager()->setDefaultData($player);
        $permanentStatsApi->getStatsManager()->setDefaultData($player);
        if ($gameApi->isWaiting()) {
            $gameApi->setPlayerTeam($player, 0);
            $scoreboardApi->sendScoreboard($player, ScoreboardTypeIds::WAITING);
            $scoreboardApi->updateOnlinePlayers(false);
            $assistApi->create($player);
            $statsApi->create($player);
            $event->setJoinMessage(Constants::PREFIX . "§a" . $player->getName() . " §fa rejoint la partie ! §8(§7" . count(Server::getInstance()->getOnlinePlayers()) . "/" . Server::getInstance()->getQueryInformation()->getMaxPlayerCount() . "§8)");
        } else {
            if ($gameApi->hasPlayerTeam($player)) {
                $gameApi->updateNametag($player);
                $scoreboardApi->sendScoreboard($player, ScoreboardTypeIds::LAUNCH);
                $event->setJoinMessage(Constants::PREFIX . "§a" . $player->getName() . " §fest revenu dans la partie !");
            }
        }
    }

    /**
     * @param PlayerQuitEvent $event
     * @return void
     */
    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $gameApi = GameManager::getInstance();
        $statsApi = StatsManager::getInstance();
        $scoreboardApi = ScoreboardManager::getInstance();
        switch ($gameApi->getStatus()) {
            case $gameApi::WAITING_STATUS:
                if ($gameApi->hasPlayerTeam($player)) {
                    $gameApi->setPlayerTeam($player, 0);
                }
                $gameApi->removePlayerTeam($player);
                $statsApi->delete($player);
                $scoreboardApi->sendScoreboard($player, ScoreboardTypeIds::WAITING);
                $scoreboardApi->updateOnlinePlayers(true);
                $event->setQuitMessage(Constants::PREFIX . "§a" . $player->getName() . " §fa quitté la partie ! §8(§7" . (count(Server::getInstance()->getOnlinePlayers()) - 1) . "/" . Server::getInstance()->getQueryInformation()->getMaxPlayerCount() . "§8)");
                break;
            case $gameApi::LAUNCH_STATUS:
                if ($gameApi->hasPlayerTeam($player)) {
                    $event->setQuitMessage(Constants::PREFIX . "§a" . $player->getName() . " §fa quitté la partie ! Il peut se reconnecter tant que la partie ne s'est pas finie !");
                } else {
                    $event->setQuitMessage("");
                }
                break;
            case $gameApi::END_STATUS:
                $event->setQuitMessage("");
                break;
        }
    }

    /**
     * @param PlayerChatEvent $event
     * @return void
     */
    public function onChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $message = $event->getMessage();
        $chatApi = ChatManager::getInstance();
        $gameApi = GameManager::getInstance();
        $rankApi = RankManager::getInstance();
        if (!$chatApi->isInAntiSpam($player)) {
            if ($chatApi->hasSavedMessage($player)) {
                if ($chatApi->isSameMessage($player, $message)) {
                    $player->sendMessage(Constants::PREFIX . "§cVous ne pouvez pas envoyer le même message deux fois de suite.");
                    $event->cancel();
                }
            }
        } else {
            $event->cancel();
        }
        if (!$event->isCancelled()) {
            switch ($gameApi->getStatus()) {
                case $gameApi::WAITING_STATUS:
                case $gameApi::END_STATUS:
                    $event->setFormatter(new LegacyRawChatFormatter($rankApi->formatChatMessage($player, $message)));
                    break;
                case $gameApi::LAUNCH_STATUS:
                    if ($gameApi->hasPlayerTeam($player)) {
                        $playerTeam = $gameApi->getPlayerTeam($player);
                        if (str_starts_with($message, "@")) {
                            $event->setFormatter(new LegacyRawChatFormatter("§8[§bGlobal§8]" . $rankApi->formatChatMessage($player, substr($message, 1))));
                        } else {
                            $gameApi->sendMessageToAllTeamPlayers($playerTeam, $player, $message);
                            $event->cancel();
                        }
                    }
                    break;
            }
            if (!$event->isCancelled()) {
                $chatApi->setMessageToSave($player, $message);
                $chatApi->addAntiSpam($player, 2);
            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     * @return void
     */
    public function onInteract(PlayerInteractEvent $event): void {
        $block = $event->getBlock();
        $player = $event->getPlayer();
        if ($block instanceof Trapdoor || $block instanceof FenceGate) {
            if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
                if (
                    !Server::getInstance()->isOp($player->getName()) &&
                    !$player->isCreative()
                ) {
                    $event->cancel();
                }
            }
        }
    }

    /**
     * @param PlayerItemUseEvent $event
     * @return void
     */
    public function onItemUse(PlayerItemUseEvent $event): void {
        $item = $event->getItem();
        $player = $event->getPlayer();
        switch ($item->getTypeId()) {
            case ItemTypeIds::COMPASS:
                GameManager::getInstance()->showTeamSelectorMenu($player);
                break;
            case ItemTypeIds::NETHER_STAR:
                $player->sendForm(GameManagementForm::getInstance()->getMainForm());
                break;
            case ItemTypeIds::EMERALD:
                $player->sendForm(Zeno::getInstance()->getStatsApi()->getStatsManager()->getMainLeaderboardForm());
                break;
            case ItemTypeIds::BOOK:
                $player->sendForm(MatchSummaryForm::getInstance()->getMainForm());
                break;
        }
    }

    /**
     * @param PlayerItemConsumeEvent $event
     * @return void
     */
    public function onItemConsume(PlayerItemConsumeEvent $event): void {
        $item = $event->getItem();
        $player = $event->getPlayer();
        if ($item instanceof GoldenApple) {
            $gameApi = GameManager::getInstance();
            if ($gameApi->isDeath($player)) {
                $event->cancel();
            }
            if (!$event->isCancelled()) {
                StatsManager::getInstance()->add($player, StatsIds::GOLDEN_APPLE_EATEN);
            }
        }
    }

    /**
     * @param PlayerDropItemEvent $event
     * @return void
     */
    public function onDropItem(PlayerDropItemEvent $event): void {
        $item = $event->getItem();
        $player = $event->getPlayer();
        if (
            !in_array($item->getTypeId(), [ItemTypeIds::ARROW, ItemTypeIds::GOLDEN_APPLE]) ||
            GameManager::getInstance()->isDeath($player)
        ) {
            $event->cancel();
        }
    }

    /**
     * @param PlayerExhaustEvent $event
     * @return void
     */
    public function onExhaust(PlayerExhaustEvent $event): void {
        $player = $event->getPlayer();
        $player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());
        $event->cancel();
    }

    /***
     * @param PlayerMoveEvent $event
     * @return void
     */
    public function onMove(PlayerMoveEvent $event): void {
        $gameApi = GameManager::getInstance();
        $player = $event->getPlayer();
        $to = $event->getTo();
        if ($gameApi->isLaunched()) {
            if ($to->y <= 0) {
                $player->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_VOID, 100.0));
            }
        }
    }

    /**
     * @param PlayerMissSwingEvent $event
     * @return void
     */
    public function onMissSwing(PlayerMissSwingEvent $event): void {
        $player = $event->getPlayer();
        $player->broadcastAnimation(new ArmSwingAnimation($player), $player->getViewers());
        $event->cancel();
    }

    /**
     * @param PlayerItemEnchantEvent $event
     * @return void
     */
    public function onItemEnchant(PlayerItemEnchantEvent $event): void {
        $event->cancel();
    }

    /**
     * @param PlayerToggleSwimEvent $event
     * @return void
     */
    public function onToggleSwim(PlayerToggleSwimEvent $event): void {
        $event->cancel();
    }

}
