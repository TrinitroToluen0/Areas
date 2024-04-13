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
use pocketmine\player\Player;
use pocketmine\entity\effect\StringToEffectParser;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\GameMode;

class Main extends PluginBase implements Listener
{

    public static Main $instance;
    public array $areas = [];
    public array $inArea;
    public array $playerEffects;
    private const EFFECT_MAX_DURATION = 2147483647;

    public function onEnable(): void
    {
        self::$instance = $this;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        foreach ($this->getConfig()->get("areas") as $areaName => $areaData) {
            $this->constructAreas($areaName, $areaData);
        }
    }

    public function constructAreas(string $areaName, array $areaData)
    {
        // TODO: Move this to the Area __constructor()
        $area = new Area();
        $area->setName($areaName);
        $area->setBlockPlace($areaData["blockPlace"]);
        $area->setBlockBreak($areaData["blockBreak"]);
        $area->setBlockInteract($areaData["blockInteract"]);
        $area->setEntityDamageFlag($areaData["entityDamage"]);
        $area->setX1($areaData["x1"]);
        $area->setY1($areaData["y1"]);
        $area->setZ1($areaData["z1"]);
        $area->setX2($areaData["x2"]);
        $area->setY2($areaData["y2"]);
        $area->setZ2($areaData["z2"]);
        $area->setWorld($areaData["world"]);
        $area->setEffects($areaData["effects"]);
        array_push($this->areas, $area);
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $playerName = $event->getPlayer()->getName();
        if(isset($this->inArea[$playerName])) unset($this->inArea[$playerName]);
        if (isset($this->playerEffects[$playerName])) unset($this->playerEffects[$playerName]);
    }


    public function onMove(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();
        /** @var Area $area */
        foreach ($this->areas as $area) {
            $isInArea = $area->isInside($player->getPosition());
            $name = $area->getName();

            // Check if the area state has changed
            if (!isset($this->inArea[$name]) || $isInArea !== $this->inArea[$name]) {
                $this->inArea[$name] = $isInArea;

                if ($isInArea) {
                    $this->playerEffects[$player->getName()] = $player->getEffects()->all();
                    $messageEntering = $this->getConfig()->get('message-entering');
                    if ($messageEntering) {
                        $message = str_replace('{AREA}', $area->getName(), $messageEntering);
                        $player->sendMessage($message);
                    }
                    foreach($area->getEffects() as $effectData) {
                        $effectData = explode('-', $effectData);
                        $effectName = $effectData[0];
                        $amplifier = (int) ($effectData[1] ?? 1) - 1;
                        $effect = StringToEffectParser::getInstance()->parse($effectName);
                        if (is_null($effect)) continue;
                        $player->getEffects()->add(new EffectInstance($effect, self::EFFECT_MAX_DURATION, $amplifier, false));
                    }
                } else {
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
    }


    /**
     * @priority HIGHEST
     * @handleCancelled true
     */
    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        /** @var Area $area */
        foreach ($this->areas as $area) {
            foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
                if (!$area->isInside($block->getPosition())) continue;
                if ($area->canPlaceBlocks() === true) {
                    if ($event->isCancelled()) $event->uncancel();
                } elseif ($area->canPlaceBlocks() === false) {
                    $event->cancel();
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @handleCancelled true
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();
        /** @var Area $area */
        foreach ($this->areas as $area) {
            if (!$area->isInside($block->getPosition())) continue;
            if ($area->canBreakBlocks() === true) {
                if ($event->isCancelled()) $event->uncancel();
            } elseif ($area->canBreakBlocks() === false) {
                $event->cancel();
            }
        }
    }


    /**
     * @priority HIGHEST
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
            } elseif ($area->canInteract() === false) {
                $event->cancel();
            }
        }
    }

    /**
     * @priority HIGHEST
     * @handleCancelled true
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();
        /** @var Area $area */
        foreach ($this->areas as $area) {
            if (!$area->isInside($entity->getPosition())) continue;
            if ($area->canEntitiesBeDamaged() === true) {
                if($entity instanceof Player && ($entity->getGamemode() === GameMode::CREATIVE() || $entity->getGamemode() === GameMode::ADVENTURE())) continue; 
                if ($event->isCancelled()) $event->uncancel();
            } elseif ($area->canEntitiesBeDamaged() === false) {
                $event->cancel();
            }
        }
    }
}