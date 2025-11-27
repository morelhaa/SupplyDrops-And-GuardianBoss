<?php

declare(strict_types=1);

namespace SupplyDrop\entities;

use pocketmine\block\VanillaBlocks;
use pocketmine\block\tile\Chest;
use pocketmine\world\Position;
use pocketmine\player\Player;
use pocketmine\inventory\Inventory;
use SupplyDrop\Main;

class SupplyChest {

    private Main $plugin;
    private Position $position;
    private int $id;
    private bool $isProtected = true;
    private int $protectionTime;
    private ?Chest $tile = null;

    public function __construct(Position $position, Main $plugin) {
        $this->plugin = $plugin;
        $this->position = $position;
        $this->id = spl_object_id($this);

        $this->protectionTime = $this->plugin->getConfig()->getNested("supply.protected_time", 300);

        $this->spawn();
    }

    private function spawn(): void {
        $world = $this->position->getWorld();

        $world->setBlock($this->position, VanillaBlocks::CHEST());

        $tile = $world->getTile($this->position);
        if ($tile instanceof Chest) {
            $this->tile = $tile;
            $this->fillChest();

            $tile->setName("§6Supply Drop");
        }
    }

    private function fillChest(): void {
        if ($this->tile === null) return;

        $inventory = $this->tile->getInventory();
        $loot = $this->plugin->getLootManager()->generateLoot();

        foreach ($loot as $item) {
            $inventory->addItem($item);
        }
    }

    public function canOpen(Player $player): bool {
        if (!$this->isProtected) {
            return true;
        }

        return false;
    }

    public function setProtected(bool $protected): void {
        $this->isProtected = $protected;
    }

    public function isProtected(): bool {
        return $this->isProtected;
    }

    public function getPosition(): Position {
        return $this->position;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getTile(): ?Chest {
        return $this->tile;
    }

    public function getInventory(): ?Inventory {
        return $this->tile?->getInventory();
    }

    public function remove(): void {
        $world = $this->position->getWorld();

        $world->setBlock($this->position, VanillaBlocks::AIR());

        $this->tile = null;
    }

    public function exists(): bool {
        $world = $this->position->getWorld();
        $block = $world->getBlock($this->position);

        return $block->getTypeId() === VanillaBlocks::CHEST()->getTypeId();
    }
}
