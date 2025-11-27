<?php

namespace SupplyDrop\menus;

use muqsit\invmenu\InvMenuTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use virions\muqsit\invmenu\InvMenu;

class LootConfigMenu
{
    public function open(Player $player) : void
    {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);

        $menu->setName(TF::AQUA . "Configuración del Loot");

        $inventory = $menu->getInventory();

        $inventory->setItem(20, VanillaItems::BOOK()
            ->setCustomName(TF::YELLOW . "Ver Loot Actual")
        );

        $inventory->setItem(22, VanillaItems::CHEST()
            ->setCustomName(TF::GREEN . "Agregar Ítem al Loot")
        );

        $inventory->setItem(24, VanillaItems::BARRIER()
            ->setCustomName(TF::RED . "Limpiar Loot")
        );

        $menu->setListener(function(Player $player, $itemClicked, int $slot) : bool {

            if($itemClicked->isNull()) {
                return true; 
            }

            $name = $itemClicked->getCustomName();

            switch($name){

                case TF::YELLOW . "Ver Loot Actual":
                    $player->sendMessage(TF::GOLD . "→ Mostrando loot actual...");
                    break;

                case TF::GREEN . "Agregar Ítem al Loot":
                    $player->sendMessage(TF::GREEN . "→ Añadiendo ítem...");
                    break;

                case TF::RED . "Limpiar Loot":
                    $player->sendMessage(TF::RED . "→ Loot eliminado.");
                    break;
            }

            return true;
        });

        $menu->send($player);
    }
}
