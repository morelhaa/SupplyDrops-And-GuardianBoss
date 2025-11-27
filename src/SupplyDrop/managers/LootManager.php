<?php

declare(strict_types=1);

namespace SupplyDrop\managers;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\Config;
use SupplyDrop\Main;

class LootManager {

    private Main $plugin;
    private array $lootTable = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->loadLootTable();
    }

    private function loadLootTable(): void {
        $config = $this->plugin->getConfig();
        $items = $config->get("loot")["items"] ?? [];

        foreach ($items as $itemData) {
            $this->lootTable[] = [
                "item" => $itemData["item"] ?? "diamond",
                "count" => $itemData["count"] ?? 1,
                "chance" => $itemData["chance"] ?? 100
            ];
        }

        // Si no hay items, agregar defaults
        if (empty($this->lootTable)) {
            $this->lootTable = [
                ["item" => "diamond", "count" => 5, "chance" => 50],
                ["item" => "iron_ingot", "count" => 10, "chance" => 80],
                ["item" => "golden_apple", "count" => 3, "chance" => 60]
            ];
        }
    }

    public function saveLootTable(): void {
        $config = $this->plugin->getConfig();
        $config->set("loot", ["items" => $this->lootTable]);
        $config->save();
    }

    public function addLootItem(string $itemName, int $count, int $chance): void {
        $this->lootTable[] = [
            "item" => $itemName,
            "count" => $count,
            "chance" => $chance
        ];
        $this->saveLootTable();
    }

    public function removeLootItem(int $index): void {
        if (isset($this->lootTable[$index])) {
            unset($this->lootTable[$index]);
            $this->lootTable = array_values($this->lootTable);
            $this->saveLootTable();
        }
    }

    public function getLootTable(): array {
        return $this->lootTable;
    }

    public function clearLootTable(): void {
        $this->lootTable = [];
        $this->saveLootTable();
    }

    /**
     * @return Item[]
     */
    public function generateLoot(): array {
        $items = [];

        foreach ($this->lootTable as $lootData) {
            if (mt_rand(1, 100) <= $lootData["chance"]) {
                $item = StringToItemParser::getInstance()->parse($lootData["item"]);
                if ($item !== null) {
                    $item->setCount($lootData["count"]);
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    public function getItemByName(string $name): ?Item {
        return StringToItemParser::getInstance()->parse($name);
    }
}