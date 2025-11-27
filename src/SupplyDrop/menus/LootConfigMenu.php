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
        // Crear menú doble cofre (PM5 / InvMenu 5.x.x)
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);

        $menu->setName(TF::AQUA . "Configuración del Loot");

        $inventory = $menu->getInventory();

        /*
         * BOTONES DEL MENÚ (puedes editar a tu gusto)
         * Organizados al centro para mayor estética
         */

        // Botón: Ver Loot actual
        $inventory->setItem(20, VanillaItems::BOOK()
            ->setCustomName(TF::YELLOW . "Ver Loot Actual")
        );

        // Botón: Añadir ítem
        $inventory->setItem(22, VanillaItems::CHEST()
            ->setCustomName(TF::GREEN . "Agregar Ítem al Loot")
        );

        // Botón: Limpiar loot
        $inventory->setItem(24, VanillaItems::BARRIER()
            ->setCustomName(TF::RED . "Limpiar Loot")
        );

        /*
         * Listener para manejar clics
         */
        $menu->setListener(function(Player $player, $itemClicked, int $slot) : bool {

            if($itemClicked->isNull()) {
                return true; // Se clickeó aire
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