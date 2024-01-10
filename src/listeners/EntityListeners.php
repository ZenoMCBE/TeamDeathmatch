<?php

namespace tdm\listeners;

use pocketmine\scheduler\ClosureTask;
use tdm\managers\{AssistManager, GameManager, MapManager, StatsManager};
use tdm\TeamDeathmatch;
use tdm\utils\Constants;
use tdm\utils\ids\StatsIds;
use tdm\utils\Utils;
use pocketmine\entity\animation\HurtAnimation;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\{EntityItemPickupEvent,
    EntitySpawnEvent,
    EntityCombustEvent,
    EntityDamageByChildEntityEvent,
    EntityDamageByEntityEvent,
    EntityDamageEvent,
    EntityExplodeEvent,
    EntityPreExplodeEvent,
    EntityShootBowEvent,
    EntityTrampleFarmlandEvent,
    ItemSpawnEvent,
    ProjectileLaunchEvent};
use pocketmine\event\Listener;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class EntityListeners implements Listener {

    /**
     * @param EntityCombustEvent $event
     * @return void
     */
    public function onCombust(EntityCombustEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $gameApi = GameManager::getInstance();
            if ($gameApi->isWaiting() || $gameApi->isEnded()) {
                $event->cancel();
            }
        }
    }

    /**
     * @param EntityDamageEvent $event
     * @return void
     */
    public function onDamage(EntityDamageEvent $event): void {
        $cause = $event->getCause();
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $gameApi = GameManager::getInstance();
            $mapApi = MapManager::getInstance();
            if (!$gameApi->isSpectator($entity)) {
                switch ($gameApi->getStatus()) {
                    case $gameApi::WAITING_STATUS:
                        if ($cause === $event::CAUSE_VOID) {
                            Utils::teleportToWaitingMap($entity);
                        }
                        $event->cancel();
                        break;
                    case $gameApi::LAUNCH_STATUS:
                        if ($cause === $event::CAUSE_VOID) {
                            $lastDamageCause = $entity->getLastDamageCause();
                            if (!is_null($lastDamageCause)) {
                                switch ($lastDamageCause) {
                                    case $lastDamageCause instanceof EntityDamageByEntityEvent:
                                    case $lastDamageCause instanceof EntityDamageByChildEntityEvent:
                                        if (!is_null($lastDamageCause->getDamager())) {
                                            $damager = $lastDamageCause->getDamager();
                                            if ($damager instanceof Player) {
                                                $gameApi->onDeath($entity, $damager, $lastDamageCause, true);
                                            }
                                        }
                                        break;
                                    default:
                                        $mapApi->teleportToTeamSpawn($entity);
                                        break;
                                }
                            } else {
                                $mapApi->teleportToTeamSpawn($entity);
                            }
                            $event->cancel();
                        } else if ($cause === $event::CAUSE_FALL) {
                            $event->cancel();
                        }
                        break;
                    case $gameApi::END_STATUS:
                        if ($cause === $event::CAUSE_VOID) {
                            Utils::teleportToEndedMap($entity);
                        }
                        $event->cancel();
                        break;
                }
            } else {
                $event->cancel();
            }
        }
    }

    /**
     * @param EntityDamageByEntityEvent $event
     * @return void
     */
    public function onDamageByEntity(EntityDamageByEntityEvent $event): void {
        $entity = $event->getEntity();
        $damager = $event->getDamager();
        if ($entity instanceof Player && $damager instanceof Player) {
            $gameApi = GameManager::getInstance();
            if (
                $event->isApplicable(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) ||
                !$gameApi->hasPlayerTeam($entity) ||
                !$gameApi->hasPlayerTeam($damager) ||
                $gameApi->isInSameTeam($entity, $damager) ||
                !$gameApi->isLaunched() ||
                $gameApi->isDeath($entity) ||
                $gameApi->isDeath($damager)
            ) {
                $event->cancel();
            }
            if (!$event->isCancelled()) {
                $event->setKnockBack(Living::DEFAULT_KNOCKBACK_FORCE);
                $event->setVerticalKnockBackLimit(Living::DEFAULT_KNOCKBACK_VERTICAL_LIMIT);
                $event->setAttackCooldown(10);
                $event->setBaseDamage(round($event->getBaseDamage() * 0.63, 2));
                $finalDamage = $event->getFinalDamage();
                $assistApi = AssistManager::getInstance();
                $statsApi = StatsManager::getInstance();
                $assistApi->update($entity, $damager);
                if ($finalDamage >= $entity->getHealth()) {
                    $gameApi->onDeath($entity, $damager, $event);
                    $event->cancel();
                } else {
                    $statsApi->add($entity, StatsIds::DAMAGE_TAKEN, intval($finalDamage));
                    $statsApi->add($damager, StatsIds::DAMAGE_DEALED, intval($finalDamage));
                    if ($event->getModifier(EntityDamageEvent::MODIFIER_CRITICAL) > 0) {
                        $statsApi->add($damager, StatsIds::CRIT);
                    }
                }
            }
        }
    }

    /**
     * @param EntityDamageByChildEntityEvent $event
     * @return void
     */
    public function onDamageByChildEntity(EntityDamageByChildEntityEvent $event): void {
        $entity = $event->getEntity();
        $damager = $event->getDamager();
        $child = $event->getChild();
        if ($entity instanceof Player && $damager instanceof Player) {
            $gameApi = GameManager::getInstance();
            $statsApi = StatsManager::getInstance();
            $isSamePlayer = Utils::getPlayerName($entity, true) == Utils::getPlayerName($damager, true);
            if (
                !$gameApi->hasPlayerTeam($entity) ||
                !$gameApi->hasPlayerTeam($damager) ||
                !$gameApi->isLaunched() ||
                $gameApi->isDeath($entity) ||
                $gameApi->isDeath($damager)
            ) {
                $event->cancel();
            }
            if (!$event->isCancelled()) {
                $assistApi = AssistManager::getInstance();
                if ($child instanceof Arrow) {
                    Utils::simulateProjectileHit($entity, $damager, $child);
                    if (!$isSamePlayer) {
                        if (!$gameApi->isInSameTeam($entity, $damager)) {
                            StatsManager::getInstance()->add($damager, StatsIds::ARROW_HIT);
                            Utils::playSound($damager, "note.bell");
                            if ($entity->getName() !== $damager->getName()) {
                                $assistApi->update($entity, $damager);
                                $entity->setLastDamageCause($event);
                            }
                            if ($event->getModifier(EntityDamageEvent::MODIFIER_ABSORPTION) < 0) {
                                $event->setModifier(0.0, EntityDamageEvent::MODIFIER_ABSORPTION);
                            }
                            $event->setBaseDamage(round($event->getBaseDamage() * 0.70, 2));
                            $finalDamage = $event->getFinalDamage();
                            if ($finalDamage < $entity->getHealth()) {
                                $entity->setHealth($entity->getHealth() - $finalDamage);
                                $statsApi->add($entity, StatsIds::DAMAGE_TAKEN, intval($finalDamage));
                                $statsApi->add($damager, StatsIds::DAMAGE_DEALED, intval($finalDamage));
                                $playerHealth = round($entity->getHealth() / 2, 1);
                                $colorPlayerHealth = match (true) {
                                    $playerHealth <= 10 && $playerHealth >= 8 => TextFormat::GREEN,
                                    $playerHealth < 8 && $playerHealth >= 6 => TextFormat::YELLOW,
                                    $playerHealth < 6 && $playerHealth >= 4 => TextFormat::GOLD,
                                    $playerHealth < 4 && $playerHealth >= 2 => TextFormat::RED,
                                    $playerHealth < 2 && $playerHealth >= 0 => TextFormat::DARK_RED
                                };
                                if ($playerHealth > 0) {
                                    $damager->sendMessage(Constants::PREFIX . "§a" . $entity->getName() . " §fest désormais à " . $colorPlayerHealth . $playerHealth . " HP §f!");
                                }
                            } else {
                                $gameApi->onDeath($entity, $damager, $event);
                            }
                        }
                    } else {
                        $projectileMotion = $child->getMotion();
                        $horizontalSpeed = sqrt($projectileMotion->x ** 2*2 + $projectileMotion->z ** 2*2);
                        if ($horizontalSpeed > 0) {
                            $child->setPunchKnockback(0.25);
                            $directionVector = $entity->getDirectionVector();
                            $directionVector->y = 0.332326;
                            $entity->setMotion($directionVector);
                            $statsApi->add($damager, StatsIds::ARROW_BOOST);
                        }
                    }
                    $event->cancel();
                }
            }
        }
    }

    /**
     * @param EntityShootBowEvent $event
     * @return void
     */
    public function onShootBow(EntityShootBowEvent $event): void {
        $entity = $event->getEntity();
        $projectile = $event->getProjectile();
        if ($entity instanceof Player) {
            if ($projectile instanceof Arrow) {
                StatsManager::getInstance()->add($entity, StatsIds::ARROW_SHOT);
                $event->setForce($event->getForce() * 1.45);
            }
        }
    }

    /**
     * @param EntitySpawnEvent $event
     * @return void
     */
    public function onSpawn(EntitySpawnEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof Arrow) {
            TeamDeathmatch::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($entity): void {
                if (!$entity->isFlaggedForDespawn()) {
                    $entity->close();
                }
            }), 20*15);
        }
    }

    /**
     * @param ItemSpawnEvent $event
     * @return void
     */
    public function onItemSpawn(ItemSpawnEvent $event): void {
        $event->getEntity()->setPickupDelay(20);
    }

    /**
     * @param EntityExplodeEvent $event
     * @return void
     */
    public function onExplode(EntityExplodeEvent $event): void {
        $event->cancel();
    }

    /**
     * @param EntityPreExplodeEvent $event
     * @return void
     */
    public function onPreExplode(EntityPreExplodeEvent $event): void {
        $event->cancel();
    }

    /**
     * @param EntityTrampleFarmlandEvent $event
     * @return void
     */
    public function onTrampleFarmland(EntityTrampleFarmlandEvent $event): void {
        $event->cancel();
    }

    /**
     * @param EntityItemPickupEvent $event
     * @return void
     */
    public function onItemPickup(EntityItemPickupEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            if (!GameManager::getInstance()->hasPlayerTeam($entity)) {
                $event->cancel();
            }
        }
    }

    /**
     * @param ProjectileLaunchEvent $event
     * @return void
     */
    public function onProjectileLaunch(ProjectileLaunchEvent $event): void {
        $entity = $event->getEntity();
        $player = $entity->getOwningEntity();
        if ($player instanceof Player) {
            $gameApi = GameManager::getInstance();
            if ($entity instanceof Arrow) {
                if ($gameApi->isDeath($player)) {
                    $event->cancel();
                }
                if (!$event->isCancelled()) {
                    $entity->setPickupMode(Arrow::PICKUP_ANY);
                }
            }
        }
    }

}
