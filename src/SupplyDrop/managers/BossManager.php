<?php

declare(strict_types=1);

namespace SupplyDrop\managers;

use pocketmine\world\Position;
use SupplyDrop\Main;
use SupplyDrop\entities\GuardianBoss;
use SupplyDrop\entities\SupplyChest;

class BossManager {

    private Main $plugin;
    private array $activeBosses = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function spawnBoss(Position $position, SupplyChest $chest): GuardianBoss {
        $spawnPos = $position->add(3, 0, 3);

        $location = \pocketmine\entity\Location::fromObject(
            $spawnPos,
            $position->getWorld()
        );

        $boss = new GuardianBoss($location, $chest, $this->plugin);
        $this->activeBosses[$boss->getId()] = $boss;

        return $boss;
    }

    public function removeBoss(int $id): void {
        if (isset($this->activeBosses[$id])) {
            $this->activeBosses[$id]->flagForDespawn();
            unset($this->activeBosses[$id]);
        }
    }

    public function removeAllBosses(): void {
        foreach ($this->activeBosses as $boss) {
            $boss->flagForDespawn();
        }
        $this->activeBosses = [];
    }

    public function getBoss(int $id): ?GuardianBoss {
        return $this->activeBosses[$id] ?? null;
    }

    public function getAllActiveBosses(): array {
        return $this->activeBosses;
    }

    public function isBossActive(int $id): bool {
        return isset($this->activeBosses[$id]);
    }
}
