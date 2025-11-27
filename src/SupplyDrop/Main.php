<?php

declare(strict_types=1);

namespace SupplyDrop;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

use SupplyDrop\commands\SupplyDropCommand;
use SupplyDrop\listeners\EventListener;
use SupplyDrop\managers\SupplyManager;
use SupplyDrop\managers\BossManager;
use SupplyDrop\managers\LootManager;

use muqsit\invmenu\InvMenuHandler; // ← IMPORTANTE (ESTE ES EL BUENO)

class Main extends PluginBase {
    use SingletonTrait;

    private SupplyManager $supplyManager;
    private BossManager $bossManager;
    private LootManager $lootManager;

    protected function onLoad(): void {
        self::setInstance($this);
    }

    protected function onEnable(): void {

        // Registrar InvMenu
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

        // Cargar config
        $this->saveDefaultConfig();

        // Registrar entidades
        $this->registerEntity();

        // Inicializar managers
        $this->lootManager = new LootManager($this);
        $this->supplyManager = new SupplyManager($this);
        $this->bossManager = new BossManager($this);

        // Registrar comandos
        $this->getServer()->getCommandMap()->register(
            "supplydrop",
            new SupplyDropCommand($this)
        );

        // Registrar listeners
        $this->getServer()->getPluginManager()->registerEvents(
            new EventListener($this),
            $this
        );

        $this->getLogger()->info("§aSupplyDrop plugin enabled!");
    }

    private function registerEntity(): void {
        $entityFactory = \pocketmine\entity\EntityFactory::getInstance();

        $entityFactory->register(\SupplyDrop\entities\GuardianBoss::class,
            function(\pocketmine\world\World $world, \pocketmine\nbt\tag\CompoundTag $nbt): \SupplyDrop\entities\GuardianBoss {
                return new \SupplyDrop\entities\GuardianBoss(
                    \pocketmine\entity\EntityDataHelper::parseLocation($nbt, $world),
                    null,
                    $this
                );
            },
            ['GuardianBoss', 'minecraft:wither_skeleton']
        );
    }

    protected function onDisable(): void {
        $this->bossManager->removeAllBosses();
        $this->supplyManager->removeAllDrops();
    }

    public function getSupplyManager(): SupplyManager {
        return $this->supplyManager;
    }

    public function getBossManager(): BossManager {
        return $this->bossManager;
    }

    public function getLootManager(): LootManager {
        return $this->lootManager;
    }
}
