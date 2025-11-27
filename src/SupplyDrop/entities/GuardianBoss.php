<?php

declare(strict_types=1);

namespace SupplyDrop\entities;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\world\Position;
use SupplyDrop\Main;
use SupplyDrop\tasks\BossAITask;
use SupplyDrop\tasks\BossBarTask;

class GuardianBoss extends Living {

    private Main $plugin;
    private ?SupplyChest $chest = null;
    private Position $homePosition;
    private ?Player $target = null;
    private int $lightningCooldown = 0;
    private BossBarTask $bossBarTask;

    public static function getNetworkTypeId(): string {
        return EntityIds::WITHER_SKELETON;
    }

    public function __construct(Location $location, ?SupplyChest $chest, Main $plugin) {
        $this->plugin = $plugin;
        if ($chest !== null) {
            $this->chest = $chest;
        }
        $this->homePosition = $location->asPosition();

        parent::__construct($location, null);

        $this->setNameTagAlwaysVisible(true);
        $this->setNameTagVisible(true);
        $this->updateNameTag();
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);

        $health = (int) $this->plugin->getConfig()->getNested("boss.health", 200.0);
        $this->setMaxHealth($health);
        $this->setHealth($health);

        $this->setNameTagAlwaysVisible(true);
        $this->setNameTagVisible(true);

        $this->spawnToAll();

        $this->plugin->getScheduler()->scheduleRepeatingTask(
            new BossAITask($this, $this->plugin),
            20
        );

        $this->bossBarTask = new BossBarTask($this);
        $this->plugin->getScheduler()->scheduleRepeatingTask($this->bossBarTask, 10);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(2.4, 0.7); 
    }

    public function getName(): string {
        return $this->plugin->getConfig()->getNested("boss.name", "§c§lGUARDIAN BOSS");
    }

    private function updateNameTag(): void {
        $health = round($this->getHealth(), 1);
        $maxHealth = round($this->getMaxHealth(), 1);
        $percentage = ($health / $maxHealth) * 100;

        $color = "§a";
        if ($percentage < 75) $color = "§e";
        if ($percentage < 50) $color = "§6";
        if ($percentage < 25) $color = "§c";

        $this->setNameTag($this->getName() . "\n" . $color . "❤ " . $health . "/" . $maxHealth);
    }

    public function attack(EntityDamageEvent $source): void {
        parent::attack($source);

        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();
            if ($damager instanceof Player) {
                $this->target = $damager;
            }
        }

        $this->updateNameTag();
    }

    public function getTarget(): ?Player {
        if ($this->target !== null && (!$this->target->isOnline() || $this->target->isClosed())) {
            $this->target = null;
        }
        return $this->target;
    }

    public function setTarget(?Player $target): void {
        $this->target = $target;
    }

    public function getHomePosition(): Position {
        return $this->homePosition;
    }

    public function getChest(): ?SupplyChest {
        return $this->chest;
    }

    public function setChest(SupplyChest $chest): void {
        $this->chest = $chest;
    }

    public function canUseLightning(): bool {
        return $this->lightningCooldown <= 0;
    }

    public function useLightning(): void {
        $cooldown = $this->plugin->getConfig()->getNested("boss.lightning_cooldown", 60);
        $this->lightningCooldown = $cooldown;
    }

    public function tickLightningCooldown(): void {
        if ($this->lightningCooldown > 0) {
            $this->lightningCooldown--;
        }
    }

    public function getLightningCooldown(): int {
        return $this->lightningCooldown;
    }

    protected function onDeath(): void {
        parent::onDeath();

        if (isset($this->bossBarTask)) {
            $this->bossBarTask->getHandler()?->cancel();
        }

        $this->plugin->getServer()->broadcastMessage("§c§l[!] §cThe Guardian Boss has been defeated!");

        $this->plugin->getBossManager()->removeBoss($this->getId());
    }

    public function getBossBarTask(): BossBarTask {
        return $this->bossBarTask;
    }

    protected function onDispose(): void {
        if (isset($this->bossBarTask)) {
            $this->bossBarTask->getHandler()?->cancel();
        }
        parent::onDispose();
    }
}
