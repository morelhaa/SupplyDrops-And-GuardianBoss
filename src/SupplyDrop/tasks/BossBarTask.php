<?php

declare(strict_types=1);

namespace SupplyDrop\tasks;

use pocketmine\scheduler\Task;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\types\BossBarColor;
use pocketmine\player\Player;
use SupplyDrop\entities\GuardianBoss;

class BossBarTask extends Task {

    private GuardianBoss $boss;
    private array $viewers = [];

    public function __construct(GuardianBoss $boss) {
        $this->boss = $boss;
    }

    public function onRun(): void {
        if ($this->boss->isClosed() || $this->boss->isFlaggedForDespawn()) {
            $this->removeBossBar();
            $this->getHandler()?->cancel();
            return;
        }

        $nearbyPlayers = $this->boss->getWorld()->getNearbyEntities($this->boss->getBoundingBox()->expandedCopy(50, 50, 50));
        $currentViewers = [];

        foreach ($nearbyPlayers as $entity) {
            if ($entity instanceof Player) {
                $currentViewers[$entity->getId()] = $entity;

                if (!isset($this->viewers[$entity->getId()])) {
                    $this->showBossBar($entity);
                } else {
                    // Actualizar bossbar existente
                    $this->updateBossBar($entity);
                }
            }
        }

        foreach ($this->viewers as $id => $player) {
            if (!isset($currentViewers[$id])) {
                $this->hideBossBar($player);
            }
        }

        $this->viewers = $currentViewers;
    }

    private function showBossBar(Player $player): void {
        $pk = BossEventPacket::show(
            $this->boss->getId(),
            $this->boss->getName(),
            $this->getHealthPercentage()
        );
        $pk->color = BossBarColor::RED;
        $player->getNetworkSession()->sendDataPacket($pk);

        $this->viewers[$player->getId()] = $player;
    }

    private function updateBossBar(Player $player): void {
        $pk = BossEventPacket::healthPercent(
            $this->boss->getId(),
            $this->getHealthPercentage()
        );
        $player->getNetworkSession()->sendDataPacket($pk);

        $pk2 = BossEventPacket::title(
            $this->boss->getId(),
            $this->boss->getName()
        );
        $player->getNetworkSession()->sendDataPacket($pk2);
    }

    private function hideBossBar(Player $player): void {
        if (!$player->isOnline() || $player->isClosed()) {
            return;
        }

        $pk = BossEventPacket::hide($this->boss->getId());
        $player->getNetworkSession()->sendDataPacket($pk);

        unset($this->viewers[$player->getId()]);
    }

    private function removeBossBar(): void {
        foreach ($this->viewers as $player) {
            if ($player->isOnline() && !$player->isClosed()) {
                $this->hideBossBar($player);
            }
        }
        $this->viewers = [];
    }

    private function getHealthPercentage(): float {
        $health = $this->boss->getHealth();
        $maxHealth = $this->boss->getMaxHealth();

        if ($maxHealth <= 0) {
            return 0.0;
        }

        return $health / $maxHealth;
    }

    public function getViewers(): array {
        return $this->viewers;
    }
}