<?php

namespace tdm\items\childs;

use pocketmine\entity\Location;
use pocketmine\entity\projectile\{Arrow, Projectile};
use pocketmine\event\entity\{EntityShootBowEvent, ProjectileLaunchEvent};
use pocketmine\item\{Bow,
    enchantment\ItemEnchantmentTags as EnchantmentTags,
    enchantment\VanillaEnchantments,
    ItemIdentifier,
    ItemTypeIds,
    ItemUseResult,
    VanillaItems};
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\BowShootSound;

final class CustomBow extends Bow {

    /**
     * CONSTRUCT
     */
    public function __construct() {
        parent::__construct(new ItemIdentifier(ItemTypeIds::BOW), "Bow", [EnchantmentTags::BOW]);
    }

    /**
     * @param Player $player
     * @param array $returnedItems
     * @return ItemUseResult
     */
    public function onReleaseUsing(Player $player, array &$returnedItems): ItemUseResult {
        $arrow = VanillaItems::ARROW();
        $inventory = match (true) {
            $player->getOffHandInventory()->contains($arrow) => $player->getOffHandInventory(),
            $player->getInventory()->contains($arrow) => $player->getInventory(),
            default => null
        };
        if ($player->hasFiniteResources() && is_null($inventory)) {
            return ItemUseResult::FAIL();
        }
        $diff = $player->getItemUseDuration();
        $p = $diff / 20;
        $baseForce = min((($p ** 2) + $p * 2) / 3, 1);
        $location = $player->getLocation();
        $entity = new Arrow(Location::fromObject(
            new Vector3($location->x, $location->y + $player->getEyeHeight(), $location->z),
            $player->getWorld(),
            ($location->yaw > 180 ? 360 : 0) - $location->yaw,
            -$location->pitch
        ), $player, $baseForce >= 1);
        $entity->setMotion($player->getDirectionVector());
        $infinity = $this->hasEnchantment(VanillaEnchantments::INFINITY());
        if ($infinity) {
            $entity->setPickupMode(Arrow::PICKUP_CREATIVE);
        }
        if (($punchLevel = $this->getEnchantmentLevel(VanillaEnchantments::PUNCH())) > 0) {
            $entity->setPunchKnockback($punchLevel);
        }
        if (($powerLevel = $this->getEnchantmentLevel(VanillaEnchantments::POWER())) > 0) {
            $entity->setBaseDamage($entity->getBaseDamage() + (($powerLevel + 1) / 2));
        }
        if ($this->hasEnchantment(VanillaEnchantments::FLAME())) {
            $entity->setOnFire(intdiv($entity->getFireTicks(), 20) + 100);
        }
        ($ev = new EntityShootBowEvent($player, $this, $entity, $baseForce * 3))->call();
        if (!$ev->isCancelled()) {
            $entity = $ev->getProjectile();
            $entity->setMotion($entity->getMotion()->multiply($ev->getForce()));
            if ($entity instanceof Projectile) {
                $projectileEv = new ProjectileLaunchEvent($entity);
                $projectileEv->call();
                if ($projectileEv->isCancelled()) {
                    $ev->getProjectile()->flagForDespawn();
                    return ItemUseResult::FAIL();
                }
                $ev->getProjectile()->spawnToAll();
                $location->getWorld()->addSound($location, new BowShootSound());
            } else {
                $entity->spawnToAll();
            }
            if ($player->hasFiniteResources()) {
                if (!$infinity) {
                    $inventory?->removeItem($arrow);
                }
            }
        }
        return ItemUseResult::SUCCESS();
    }

}
