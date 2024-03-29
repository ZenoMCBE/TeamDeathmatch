<?php

namespace tdm\listeners;

use pocketmine\block\inventory\BlockInventory;
use tdm\librairies\invmenu\inventory\InvMenuInventory;
use tdm\managers\GameManager;
use pocketmine\event\inventory\{InventoryOpenEvent, CraftItemEvent, InventoryTransactionEvent};
use pocketmine\event\Listener;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\transaction\action\{DropItemAction, SlotChangeAction};
use pocketmine\player\GameMode;
use pocketmine\Server;

final class InventoryListeners implements Listener {

    /**
     * @param CraftItemEvent $event
     * @return void
     */
    public function onCraftItem(CraftItemEvent $event): void {
        $event->cancel();
    }

    /**
     * @param InventoryOpenEvent $event
     * @return void
     */
    public function onOpen(InventoryOpenEvent $event): void {
        $inventory = $event->getInventory();
        if (
            isset(class_implements($inventory)[BlockInventory::class]) &&
            !$inventory instanceof InvMenuInventory
        ) {
            $event->cancel();
        }
    }

    /**
     * @param InventoryTransactionEvent $event
     * @return void
     */
    public function onTransaction(InventoryTransactionEvent $event): void {
        $gameApi = GameManager::getInstance();
        $transaction = $event->getTransaction();
        $inventories = $transaction->getInventories();
        $actions = $transaction->getActions();
        $player = $transaction->getSource();
        foreach ($inventories as $inventory) {
            if ($player->getGamemode() !== GameMode::CREATIVE()) {
                if ($inventory instanceof PlayerInventory || $inventory instanceof PlayerCursorInventory) {
                    foreach ($actions as $action) {
                        switch ($gameApi->getStatus()) {
                            case $gameApi::WAITING_STATUS:
                            case $gameApi::END_STATUS:
                                if (Server::getInstance()->isOp($player->getName())) {
                                    $event->cancel();
                                }
                                break;
                            case $gameApi::LAUNCH_STATUS:
                                if (
                                    !$action instanceof SlotChangeAction &&
                                    !$action instanceof DropItemAction
                                ) {
                                    $event->cancel();
                                }
                                break;
                        }
                    }
                } else {
                    $event->cancel();
                }
            }
        }
    }

}
