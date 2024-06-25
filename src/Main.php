<?php

declare(strict_types=1);

namespace Mencoreh\Areas;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use Mencoreh\Areas\Area;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use pocketmine\entity\effect\StringToEffectParser;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;

class Main extends PluginBase implements Listener
{
    use SingletonTrait;

    public array $areas = [];
    public array $playerEffects;
    private const EFFECT_MAX_DURATION = 2147483647;

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        self::setInstance($this);

        foreach ($this->getConfig()->get("areas") as $areaName => $areaData) {
            $area = new Area($areaName);
            array_push($this->areas, $area);
        }
    }

    private function handlePlayerMove(Player $player, Position $from, Position $to): void
    {
        if($from->equals($to)) return;

        /** @var Area $area */
        foreach ($this->areas as $area) {
            if (!$area->isInside($from) && $area->isInside($to)) {
                $this->enterArea($player, $area);
            }

            if ($area->isInside($from) && !$area->isInside($to)) {
                $this->leaveArea($player, $area);
            }
        }
    }

    private function enterArea(Player $player, Area $area): void
    {
        $messageEntering = $this->getConfig()->get('message-entering');
        if ($messageEntering) {
            $message = str_replace('{AREA}', $area->getName(), $messageEntering);
            $player->sendMessage($message);
        }
        foreach ($area->getEffects() as $effectData) {
            $effectData = explode('-', $effectData);
            $effectName = $effectData[0];
            $amplifier = (int)($effectData[1] ?? 1) - 1;
            $effect = StringToEffectParser::getInstance()->parse($effectName);
            if (!$effect) continue;

            $effectInstance = new EffectInstance($effect, self::EFFECT_MAX_DURATION, $amplifier, false);

            // Verificar si el jugador ya tiene el efecto y guardarlo en playerEffects
            if ($player->getEffects()->has($effect)) {
                $existingEffect = $player->getEffects()->get($effect);
                $this->playerEffects[$player->getName()][] = $existingEffect;
            }

            $player->getEffects()->add($effectInstance);
        }
    }

    private function leaveArea(Player $player, Area $area): void
    {

        // Limpiar los efectos otorgados por el área
        foreach ($area->getEffects() as $effectData) {
            $effectData = explode('-', $effectData);
            $effectName = $effectData[0];
            $effect = StringToEffectParser::getInstance()->parse($effectName);
            if ($effect && $player->getEffects()->has($effect)) {
                $player->getEffects()->remove($effect);
            }
        }

        // Restaurar los efectos guardados en playerEffects
        if (isset($this->playerEffects[$player->getName()])) {
            foreach ($this->playerEffects[$player->getName()] as $effect) {
                $player->getEffects()->add($effect);
            }
            unset($this->playerEffects[$player->getName()]);
        }
        $messageLeaving = $this->getConfig()->get('message-leaving');
        if ($messageLeaving) {
            $message = str_replace('{AREA}', $area->getName(), $messageLeaving);
            $player->sendMessage($message);
        }
    }


    public function clearPlayerData(Player $player): void
    {
        $playerName = $player->getName();
        if (isset($this->playerEffects[$playerName])) unset($this->playerEffects[$playerName]);
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $this->playerEffects[$player->getName()] = $player->getEffects()->all();
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $this->clearPlayerData($event->getPlayer());
    }

    public function onDeath(PlayerDeathEvent $event): void
    {
        $this->clearPlayerData($event->getPlayer());
    }

    public function onMove(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();
        $from = $event->getFrom();
        $to = $event->getTo();
        $this->handlePlayerMove($player, $from, $to);
    }

    public function onTeleport(EntityTeleportEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $from = $event->getFrom();
            $to = $event->getTo();
            $this->handlePlayerMove($entity, $from, $to);
        }
    }

    /**
     * @priority HIGH
     * @handleCancelled true
     */
    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();

        /** @var Area $area */
        foreach ($this->areas as $area) {
            foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
                if (!$area->isInside($block->getPosition())) continue;
                if ($area->canPlaceBlocks() === true) {
                    if ($event->isCancelled()) $event->uncancel();
                } elseif ($area->canPlaceBlocks() === false && !$player->hasPermission("areas.bypass")) {
                    $event->cancel();
                }
            }
        }
    }

    /**
     * @priority HIGH
     * @handleCancelled true
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        /** @var Area $area */
        foreach ($this->areas as $area) {
            if (!$area->isInside($block->getPosition())) continue;
            if ($area->canBreakBlocks() === true) {
                if ($event->isCancelled()) $event->uncancel();
            } elseif ($area->canBreakBlocks() === false && !$player->hasPermission("areas.bypass")) {
                $event->cancel();
            }
        }
    }

    /**
     * @priority HIGH
     * @handleCancelled true
     */
    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        /** @var Area $area */
        foreach ($this->areas as $area) {
            if ($area->getInteractCheckType() === "player") {
                if (!$area->isInside($player->getPosition())) continue;
            } else if ($area->getInteractCheckType() === "block") {
                if (!$area->isInside($block->getPosition())) continue;
            }
            if ($area->canInteract() === true) {
                if ($event->isCancelled()) $event->uncancel();
            } elseif ($area->canInteract() === false && !$player->hasPermission("areas.bypass")) {
                $event->cancel();
            }
        }
    }

    /**
     * @priority HIGH
     * @handleCancelled true
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        /** @var Area $area */
        foreach ($this->areas as $area) {
            if (!$area->isInside($entity->getPosition())) continue;
            if ($area->canEntitiesBeDamaged() === true) {
                if ($entity instanceof Player && $entity->isCreative()) continue;
                if ($event->isCancelled()) $event->uncancel();
            } elseif ($area->canEntitiesBeDamaged() === false) {
                if ($event instanceof EntityDamageByEntityEvent) {
                    $damager = $event->getDamager();
                    if ($damager instanceof Player && !$damager->hasPermission("areas.bypass")) {
                        $event->cancel();
                    }
                } else {
                    $event->cancel();
                }
            }
        }
    }
}
