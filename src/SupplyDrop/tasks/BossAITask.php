<?php

declare(strict_types=1);

namespace SupplyDrop\tasks;

use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\particle\FlameParticle;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\entity\Entity;
use SupplyDrop\Main;
use SupplyDrop\entities\GuardianBoss;

class BossAITask extends Task {

    private GuardianBoss $boss;
    private Main $plugin;

    public function __construct(GuardianBoss $boss, Main $plugin) {
        $this->boss = $boss;
        $this->plugin = $plugin;
    }

    public function onRun(): void {
        if ($this->boss->isClosed() || $this->boss->isFlaggedForDespawn()) {
            $this->getHandler()?->cancel();
            return;
        }

        $this->boss->tickLightningCooldown();
        if ($this->boss->getTarget() === null || !$this->boss->getTarget()->isAlive()) {
            $this->findTarget();
        }

        $target = $this->boss->getTarget();

        if ($target !== null && $target->isAlive()) {
            $distance = $this->boss->getLocation()->distance($target->getLocation());
            $returnDistance = $this->plugin->getConfig()->getNested("boss.return_distance", 30);

            if ($distance > $returnDistance) {
                $this->returnToHome();
                $target->sendMessage("§c[!] §cThe Guardian Boss returned to protect the supply drop!");
                $this->boss->setTarget(null);
                return;
            }

            $this->moveToTarget($target);
            if ($this->boss->canUseLightning() && $distance < 20 && mt_rand(1, 100) <= 75) {
                $this->spawnLightning($target);
                $this->boss->useLightning();
            }

            if ($distance < 4) {
                $this->meleeAttack($target);
            }
        } else {
            $this->findTarget();

            if ($this->boss->getTarget() === null) {
                $this->patrol();
            }
        }
    }

    private function findTarget(): void {
        $homePos = $this->boss->getHomePosition();
        $players = $this->boss->getWorld()->getNearbyEntities($this->boss->getBoundingBox()->expandedCopy(30, 20, 30)); // Mayor rango de detección

        $closestPlayer = null;
        $closestDistance = PHP_FLOAT_MAX;

        foreach ($players as $entity) {
            if ($entity instanceof Player && $entity->isAlive() && !$entity->isCreative() && !$entity->isSpectator()) {
                $distance = $this->boss->getLocation()->distance($entity->getLocation());

                if ($distance < $closestDistance) {
                    $closestDistance = $distance;
                    $closestPlayer = $entity;
                }
            }
        }

        if ($closestPlayer !== null) {
            $this->boss->setTarget($closestPlayer);
            $closestPlayer->sendMessage("§c[!] §cThe Guardian Boss has detected you!");

            $closestPlayer->getWorld()->addSound($closestPlayer->getLocation(), new \pocketmine\world\sound\GhastShootSound());
        }
    }

    private function moveToTarget(Player $target): void {
        $bossPos = $this->boss->getLocation();
        $targetPos = $target->getLocation();

        $dx = $targetPos->x - $bossPos->x;
        $dz = $targetPos->z - $bossPos->z;
        $distance = sqrt($dx * $dx + $dz * $dz);

        if ($distance > 2) {
            $speed = 0.5; // MÁS RÁPIDO (era 0.3)
            $motionX = ($dx / $distance) * $speed;
            $motionZ = ($dz / $distance) * $speed;

            $newLoc = Location::fromObject(
                $bossPos->add($motionX, 0, $motionZ),
                $bossPos->getWorld(),
                $this->calculateYaw($dx, $dz),
                0
            );

            $this->boss->teleport($newLoc);
        }
    }

    private function returnToHome(): void {
        $homePos = $this->boss->getHomePosition();
        $bossPos = $this->boss->getLocation();

        $dx = $homePos->x - $bossPos->x;
        $dz = $homePos->z - $bossPos->z;
        $distance = sqrt($dx * $dx + $dz * $dz);

        if ($distance > 1) {
            $speed = 0.5;
            $motionX = ($dx / $distance) * $speed;
            $motionZ = ($dz / $distance) * $speed;

            $newLoc = Location::fromObject(
                $bossPos->add($motionX, 0, $motionZ),
                $bossPos->getWorld(),
                $this->calculateYaw($dx, $dz),
                0
            );

            $this->boss->teleport($newLoc);
        }
    }

    private function patrol(): void {
        $homePos = $this->boss->getHomePosition();
        $bossPos = $this->boss->getLocation();
        $distance = $bossPos->distance($homePos);

        if ($distance > 5) {
            $this->returnToHome();
        }
    }

    private function spawnLightning(Player $target): void {
        $world = $target->getWorld();
        $pos = $target->getLocation();

        for ($i = 0; $i < 5; $i++) {
            $world->addParticle($pos->add(0, $i, 0), new FlameParticle());
            $world->addParticle($pos->add(0, $i, 0), new \pocketmine\world\particle\CriticalParticle());
        }

        $world->addSound($pos, new BlazeShootSound());
        $world->addSound($pos, new \pocketmine\world\sound\ExplodeSound());

        $damage = $this->plugin->getConfig()->getNested("boss.damage", 10.0);
        $target->attack(new \pocketmine\event\entity\EntityDamageEvent(
            $target,
            \pocketmine\event\entity\EntityDamageEvent::CAUSE_MAGIC,
            $damage
        ));

        $target->setOnFire(3);
        $target->sendMessage("§l§cThe Guardian Boss struck you with lightning!");
    }

    private function meleeAttack(Player $target): void {
        $damage = $this->plugin->getConfig()->getNested("boss.damage", 10.0) * 0.5;
        $target->attack(new \pocketmine\event\entity\EntityDamageEvent($target, \pocketmine\event\entity\EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage));

        $direction = $target->getLocation()->subtract($this->boss->getLocation()->x, 0, $this->boss->getLocation()->z)->normalize();
        $target->setMotion($direction->multiply(0.5)->add(0, 0.3, 0));
    }

    private function calculateYaw(float $dx, float $dz): float {
        return atan2($dz, $dx) * 180 / M_PI - 90;
    }
}