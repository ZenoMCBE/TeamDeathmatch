<?php

namespace tdm\managers;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\GameMode;
use pocketmine\Server;
use pocketmine\utils\Limits;
use tdm\items\childs\CustomBow;
use tdm\utils\ids\KitIds;
use tdm\utils\Utils;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

final class KitManager {

    use SingletonTrait;

    /**
     * @param Player $player
     * @param string $kit
     * @return void
     */
    public function send(Player $player, string $kit): void {
        Utils::prepare($player);
        $gameApi = GameManager::getInstance();
        $voteApi = VoteManager::getInstance();
        switch ($kit) {
            case KitIds::WAITING:
                $teamSelector = VanillaItems::COMPASS()->setCustomName("§r§l§q» §r§aSélecteur d'équipe §l§q«");
                $mapVote = VanillaItems::PAPER()->setCustomName("§r§l§q» §r§aVote de map §l§q«");
                $gameManagement = VanillaItems::NETHER_STAR()->setCustomName("§r§l§q» §r§aGestion de la partie §l§q«");
                $permanentStats = VanillaItems::EMERALD()->setCustomName("§r§l§q» §r§aStatistiques/Classements §l§q«");

                $playerInventory = $player->getInventory();

                $playerInventory->setItem(0, $teamSelector);
                $playerInventory->setItem(8, $permanentStats);
                if (RankManager::getInstance()->isHoster($player)) {
                    $playerInventory->setItem(4, $gameManagement);
                }
                if ($voteApi->isActive()) {
                    if (!$playerInventory->contains(VanillaItems::PAPER())) {
                        $playerInventory->setItem(1, $mapVote);
                    }
                }
                break;
            case KitIds::GAME:
                $playerTeam = $gameApi->getPlayerTeam($player);
                $teamColorString = $gameApi->getTeamColor($playerTeam);
                $teamColor = $gameApi->getColorByColorId($teamColorString);

                $helmet = VanillaItems::IRON_HELMET()->setUnbreakable();
                $chestplate = VanillaItems::LEATHER_TUNIC()->setCustomColor($teamColor)->setUnbreakable();
                $leggings = VanillaItems::CHAINMAIL_LEGGINGS()->setUnbreakable();
                $boots = VanillaItems::IRON_BOOTS()->setUnbreakable();

                $sword = VanillaItems::STONE_SWORD()->setUnbreakable();
                $bow = new CustomBow();
                $bow->setUnbreakable();
                $goldenApple = VanillaItems::GOLDEN_APPLE()->setCount(1);
                $arrows = VanillaItems::ARROW()->setCount(12);

                $playerInventory = $player->getInventory();
                $playerArmorInventory = $player->getArmorInventory();

                $playerInventory->setItem(0, $sword);
                $playerInventory->setItem(1, $bow);
                $playerInventory->setItem(2, $goldenApple);
                $playerInventory->setItem(8, $arrows);

                $playerArmorInventory->setHelmet($helmet);
                $playerArmorInventory->setChestplate($chestplate);
                $playerArmorInventory->setLeggings($leggings);
                $playerArmorInventory->setBoots($boots);

                $player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), Limits::INT32_MAX, 1, false));
                break;
            case KitIds::END:
                $matchSummary = VanillaItems::BOOK()->setCustomName("§r§l§q» §r§aRésumé du match §l§q«");

                $player->getInventory()->setItem(4, $matchSummary);
                break;
            case KitIds::SPECTATOR:
                $teleporter = VanillaItems::CLOCK()->setCustomName("§r§l§q» §r§aTéléporteur §l§q«");

                $player->getInventory()->setItem(4, $teleporter);

                foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                    $onlinePlayer->hidePlayer($player);
                }

                $player->setAllowFlight(true);
                break;
        }
    }

    /**
     * @param Player $player
     * @return void
     */
    public function refill(Player $player): void {
        $player->getInventory()->addItem(VanillaItems::GOLDEN_APPLE()->setCount(1), VanillaItems::ARROW()->setCount(4));
    }

}
