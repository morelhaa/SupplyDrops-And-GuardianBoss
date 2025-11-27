<?php

declare(strict_types=1);

namespace SupplyDrop\managers;

use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\sound\AnvilFallSound;
use pocketmine\scheduler\ClosureTask;
use SupplyDrop\Main;
use SupplyDrop\entities\SupplyChest;

class SupplyManager {

    private Main $plugin;
    private array $activeDrops = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function spawnSupplyDrop(Position $position): void {
        $world = $position->getWorld();

        if ($this->plugin->getConfig()->getNested("supply.announce_spawn", true)) {
            $this->plugin->getServer()->broadcastMessage("§e§l[!] §6A Supply Drop is falling from the sky!");
        }

        $dropPos = $position->add(0, 50, 0);

        $world->addParticle($dropPos, new HugeExplodeParticle());
        $world->addSound($dropPos, new AnvilFallSound());

        $this->plugin->getScheduler()->scheduleDelayedTask(
            new ClosureTask(function() use ($position, $world): void {
                $chest = new SupplyChest($position, $this->plugin);
                $this->activeDrops[$chest->getId()] = $chest;

                $this->plugin->getBossManager()->spawnBoss($position, $chest);

                $world->addParticle($position, new HugeExplodeParticle());
                $world->addSound($position, new AnvilFallSound());
                $x = (int)$position->x;
                $y = (int)$position->y;
                $z = (int)$position->z;
                $this->plugin->getServer()->broadcastMessage("§a§l[!] §aSupply Drop landed at X:$x Y:$y Z:$z");
            }),
            40
        );
    }

    public function removeSupplyDrop(int $id): void {
        if (isset($this->activeDrops[$id])) {
            $this->activeDrops[$id]->remove();
            unset($this->activeDrops[$id]);
        }
    }

    public function removeAllDrops(): void {
        foreach ($this->activeDrops as $drop) {
            $drop->remove();
        }
        $this->activeDrops = [];
    }

    public function getActiveDrop(int $id): ?SupplyChest {
        return $this->activeDrops[$id] ?? null;
    }

    public function getAllActiveDrops(): array {
        return $this->activeDrops;
    }
}
