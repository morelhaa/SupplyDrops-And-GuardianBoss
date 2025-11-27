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
    /** @var SupplyChest[] */
    private array $activeDrops = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function spawnSupplyDrop(Position $position): void {
        $world = $position->getWorld();

        // Anunciar spawn si está habilitado
        if ($this->plugin->getConfig()->getNested("supply.announce_spawn", true)) {
            $this->plugin->getServer()->broadcastMessage("§e§l[!] §6A Supply Drop is falling from the sky!");
        }

        // Crear efecto de caída desde el cielo
        $dropPos = $position->add(0, 50, 0);

        // Partículas y sonido inicial
        $world->addParticle($dropPos, new HugeExplodeParticle());
        $world->addSound($dropPos, new AnvilFallSound());

        // Crear cofre en el suelo después de 2 segundos (40 ticks)
        $this->plugin->getScheduler()->scheduleDelayedTask(
            new ClosureTask(function() use ($position, $world): void {
                $chest = new SupplyChest($position, $this->plugin);
                $this->activeDrops[$chest->getId()] = $chest;

                // Spawner boss guardian
                $this->plugin->getBossManager()->spawnBoss($position, $chest);

                // Efectos de impacto
                $world->addParticle($position, new HugeExplodeParticle());
                $world->addSound($position, new AnvilFallSound());

                // Anunciar ubicación
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

    /**
     * @return SupplyChest[]
     */
    public function getAllActiveDrops(): array {
        return $this->activeDrops;
    }
}