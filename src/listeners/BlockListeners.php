<?php

namespace zenogames\listeners;

use zenogames\managers\GameManager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockDeathEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockMeltEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\Listener;
use pocketmine\Server;

final class BlockListeners implements Listener {

    /**
     * @param BlockBreakEvent $event
     * @return void
     */
    public function onBreak(BlockBreakEvent $event): void {
        $gameApi = GameManager::getInstance();
        $player = $event->getPlayer();
        if (
            ($gameApi->isWaiting() && !$player->isCreative()) ||
            !Server::getInstance()->isOp($player->getName()) ||
            $gameApi->isLaunched()
        ) {
            $event->cancel();
        }
    }

    /**
     * @param BlockPlaceEvent $event
     * @return void
     */
    public function onPlace(BlockPlaceEvent $event): void {
        $gameApi = GameManager::getInstance();
        $player = $event->getPlayer();
        if (
            ($gameApi->isWaiting() && !$player->isCreative()) ||
            !Server::getInstance()->isOp($player->getName()) ||
            $gameApi->isLaunched()
        ) {
            $event->cancel();
        }
    }

    /**
     * @param BlockBurnEvent $event
     * @return void
     */
    public function onBurn(BlockBurnEvent $event): void {
        $event->cancel();
    }

    /**
     * @param BlockDeathEvent $event
     * @return void
     */
    public function onDeath(BlockDeathEvent $event): void {
        $event->cancel();
    }

    /**
     * @param BlockFormEvent $event
     * @return void
     */
    public function onForm(BlockFormEvent $event): void {
        $event->cancel();
    }

    /**
     * @param BlockGrowEvent $event
     * @return void
     */
    public function onGrow(BlockGrowEvent $event): void {
        $event->cancel();
    }

    /**
     * @param BlockMeltEvent $event
     * @return void
     */
    public function onMelt(BlockMeltEvent $event): void {
        $event->cancel();
    }

    /**
     * @param BlockSpreadEvent $event
     * @return void
     */
    public function onSpread(BlockSpreadEvent $event): void {
        $event->cancel();
    }

    /**
     * @param BlockUpdateEvent $event
     * @return void
     */
    public function onUpdate(BlockUpdateEvent $event): void {
        $event->cancel();
    }

    /**
     * @param LeavesDecayEvent $event
     * @return void
     */
    public function onLeavesDecay(LeavesDecayEvent $event): void {
        $event->cancel();
    }

    /**
     * @param SignChangeEvent $event
     * @return void
     */
    public function onSignChange(SignChangeEvent $event): void {
        $event->cancel();
    }

    /**
     * @param StructureGrowEvent $event
     * @return void
     */
    public function onStructureGrow(StructureGrowEvent $event): void {
        $event->cancel();
    }

}
