<?php

declare(strict_types=1);

namespace Mencoreh\AreaPermissions;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;

class Main extends PluginBase implements Listener
{

    private $inArea = [];

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
    }

    public function onMove(PlayerMoveEvent $event): void
    {

        if ($event->isCancelled()) {
            return;
        }

        $player = $event->getPlayer();
        $name = $player->getName();
        $pos = $player->getLocation();
        $x = intval($pos->getX());
        $y = intval($pos->getY());
        $z = intval($pos->getZ());
        $world = $player->getWorld()->getFolderName();

        // Get the areas from the configuration file
        $areas = $this->getConfig()->get('areas');

        if ($areas === false) {
            $this->getLogger()->error("Failed to load areas from the config, please fix the configuration.");
            return;
        }

        foreach ($areas as $areaName => $areaData) {

            // Checks if the player is in the area's world
            if ($world !== $areaData['world']) {
                continue;
            }

            // Define the coordinates of the area
            $x1 = $areaData['x1'];
            $y1 = $areaData['y1'];
            $z1 = $areaData['z1'];
            $x2 = $areaData['x2'];
            $y2 = $areaData['y2'];
            $z2 = $areaData['z2'];

            // Checks if the player is inside the area
            $isInArea = 
                $x >= min($x1, $x2) && $x <= max($x1, $x2) &&
                $y >= min($y1, $y2) && $y <= max($y1, $y2) &&
                $z >= min($z1, $z2) && $z <= max($z1, $z2);

            // Check if the area state has changed
            if (!isset($this->inArea[$name]) || $isInArea !== $this->inArea[$name]) {
                $this->inArea[$name] = $isInArea;
                $attachment = $player->addAttachment($this);

                if ($isInArea) {
                    $messageEntering = $this->getConfig()->get('message-entering');
                    if (isset($messageEntering) && $messageEntering != false && !empty($messageEntering)) {
                        $message = str_replace('{AREA}', $areaName, $messageEntering);
                        $player->sendMessage($message);
                    }
                    foreach ($areaData['permissions'] as $permission) {
                        $attachment->setPermission($permission, true);
                    }
                } else {
                    $messageLeaving = $this->getConfig()->get('message-leaving');
                    if (isset($messageLeaving) && $messageLeaving != false && !empty($messageLeaving)) {
                        $message = str_replace('{AREA}', $areaName, $messageLeaving);
                        $player->sendMessage($message);
                    }
                    foreach ($areaData['permissions'] as $permission) {
                        $attachment->setPermission($permission, false);
                    }
                }
            }
        }
    }
}
