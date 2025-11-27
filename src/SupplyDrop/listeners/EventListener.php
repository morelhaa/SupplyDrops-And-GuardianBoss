<?php

declare(strict_types=1);

namespace SupplyDrop\listeners;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\tile\Chest;
use pocketmine\player\Player;
use SupplyDrop\Main;
use SupplyDrop\entities\GuardianBoss;

class EventListener implements Listener {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        // Verificar si es un cofre de supply drop
        if ($block->getTypeId() === VanillaBlocks::CHEST()->getTypeId()) {
            $position = $block->getPosition();

            foreach ($this->plugin->getSupplyManager()->getAllActiveDrops() as $drop) {
                if ($drop->getPosition()->equals($position)) {
                    // No permitir romper el cofre
                    $event->cancel();
                    $player->sendMessage("§c§l[!] §cYou cannot break the supply drop chest!");
                    return;
                }
            }
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        // Verificar si está intentando abrir un cofre de supply drop
        if ($block->getTypeId() === VanillaBlocks::CHEST()->getTypeId()) {
            $position = $block->getPosition();

            foreach ($this->plugin->getSupplyManager()->getAllActiveDrops() as $drop) {
                if ($drop->getPosition()->equals($position)) {

                    // Verificar si el cofre está protegido
                    if ($drop->isProtected()) {
                        $event->cancel();
                        $player->sendMessage("§c§l[!] §cThe supply drop is protected by the Guardian Boss!");
                        $player->sendMessage("§e§l[!] §eDefeat the boss first to claim the loot!");
                        return;
                    }

                    // Si el boss fue derrotado, permitir abrir
                    $player->sendMessage("§a§l✔ §aYou claimed the supply drop!");

                    // Remover el supply drop después de ser abierto
                    $this->plugin->getScheduler()->scheduleDelayedTask(
                        new \pocketmine\scheduler\ClosureTask(function() use ($drop): void {
                            $this->plugin->getSupplyManager()->removeSupplyDrop($drop->getId());
                        }),
                        100 // 5 segundos después
                    );
                    return;
                }
            }
        }
    }

    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();

        // Proteger al boss de ciertos tipos de daño
        if ($entity instanceof GuardianBoss) {
            $cause = $event->getCause();

            // Inmune a caída, fuego, ahogamiento, etc.
            if (in_array($cause, [
                EntityDamageEvent::CAUSE_FALL,
                EntityDamageEvent::CAUSE_FIRE,
                EntityDamageEvent::CAUSE_FIRE_TICK,
                EntityDamageEvent::CAUSE_LAVA,
                EntityDamageEvent::CAUSE_DROWNING,
                EntityDamageEvent::CAUSE_SUFFOCATION,
                EntityDamageEvent::CAUSE_VOID
            ])) {
                $event->cancel();
                return;
            }

            // Cuando el boss muere
            if ($entity->getHealth() - $event->getFinalDamage() <= 0) {
                $chest = $entity->getChest();

                // Desproteger el cofre
                if ($chest !== null && $chest->exists()) {
                    $chest->setProtected(false);
                }

                // Si hay un atacante, darle recompensa extra
                if ($event instanceof EntityDamageByEntityEvent) {
                    $damager = $event->getDamager();
                    if ($damager instanceof Player) {
                        $damager->sendMessage("§l§aYou defeated the Guardian Boss!");
                        $damager->sendMessage("§e§l[!] §eThe supply drop is now unprotected!");

                        // Broadcast
                        $this->plugin->getServer()->broadcastMessage(
                            "§6§l[!] §e" . $damager->getName() . " §edefeated the Guardian Boss!"
                        );
                    }
                }
            }
        }
    }
}