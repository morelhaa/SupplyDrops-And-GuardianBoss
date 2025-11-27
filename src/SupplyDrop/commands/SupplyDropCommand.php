<?php

declare(strict_types=1);

namespace SupplyDrop\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use SupplyDrop\Main;
use SupplyDrop\menus\LootConfigMenu;

class SupplyDropCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("supplydrop", "Manage supply drops", "/supplydrop <spawn|loot>", ["sd"]);
        $this->setPermission("supplydrop.command");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cThis command must be used in-game!");
            return false;
        }

        if (!$this->testPermission($sender)) {
            return false;
        }

        if (empty($args)) {
            $sender->sendMessage("§eSupplyDrop Commands:");
            $sender->sendMessage("§7/supplydrop spawn §f- Spawn a supply drop");
            $sender->sendMessage("§7/supplydrop loot §f- Configure loot items");
            return false;
        }

        switch (strtolower($args[0])) {
            case "spawn":
                $this->plugin->getSupplyManager()->spawnSupplyDrop($sender->getPosition());
                $sender->sendMessage("§a§aSupply drop spawned at your location!");
                break;

            case "loot":
                (new LootConfigMenu())->open($sender);
                break;

            default:
                $sender->sendMessage("§c§cUnknown subcommand. Use: spawn or loot");
                break;
        }

        return true;
    }
}
