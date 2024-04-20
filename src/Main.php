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
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;

class Main extends PluginBase implements Listener
{

    public static Main $instance;
    public array $areas = [];
    public array $playerEffects;
    private const EFFECT_MAX_DURATION = 2147483647;

    public function onEnable(): void
    {
        self::$instance = $this;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        foreach ($this->getConfig()->get("areas") as $areaName => $areaData) {
            $area = new Area($areaName);
            array_push($this->areas, $area);
        }
    }

    public function onQuit(PlayerQuitEvent|PlayerDeathEvent $event): void
    {
        $playerName = $event->getPlayer()->getName();
        if (isset($this->playerEffects[$playerName])) unset($this->playerEffects[$playerName]);
    }


    public function onMove(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();
        $from = $event->getFrom();
        $to = $event->getTo();
        if ($from->getX() === $to->getX() && $from->getY() === $to->getY() && $from->getZ() === $to->getZ()) return;

        /** @var Area $area */
        foreach ($this->areas as $area) {

            // If player enters an area
            if(!$area->isInside($from) && $area->isInside($to)) {
                $this->playerEffects[$player->getName()] = $player->getEffects()->all();
                $messageEntering = $this->getConfig()->get('message-entering');
                if ($messageEntering) {
                    $message = str_replace('{AREA}', $area->getName(), $messageEntering);
                    $player->sendMessage($message);
                }
                foreach ($area->getEffects() as $effectData) {
                    $effectData = explode('-', $effectData);
                    $effectName = $effectData[0];
                    $amplifier = (int) ($effectData[1] ?? 1) - 1;
                    $effect = StringToEffectParser::getInstance()->parse($effectName);
                    if (is_null($effect)) continue;
                    $player->getEffects()->add(new EffectInstance($effect, self::EFFECT_MAX_DURATION, $amplifier, false));
                }
            } 
            
            // If players leaves an area
            if($area->isInside($from) && !$area->isInside($to)) {
                if (isset($this->playerEffects[$player->getName()])) {
                    $player->getEffects()->clear();
                    foreach ($this->playerEffects[$player->getName()] as $effect) {
                        $player->getEffects()->add($effect);
                    }
                    if (isset($this->playerEffects[$player->getName()])) unset($this->playerEffects[$player->getName()]);
                }
                $messageLeaving = $this->getConfig()->get('message-leaving');
                if ($messageLeaving) {
                    $message = str_replace('{AREA}', $area->getName(), $messageLeaving);
                    $player->sendMessage($message);
                }
            }
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
            if($area->getInteractCheckType() === "player") {
                if (!$area->isInside($player->getPosition())) continue;
            } else if($area->getInteractCheckType() === "block") {
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
                if($entity instanceof Player && $entity->isCreative()) continue; 
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