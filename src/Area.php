<?php

declare(strict_types=1);

namespace Mencoreh\Areas;

use pocketmine\world\Position;

class Area
{

    private ?bool $blockPlace;
    private ?bool $blockBreak;
    private ?bool $blockInteract;
    private string $interactCheckType;
    private ?bool $entityDamage;
    private array $effects;

    private string $name;
    private string $world;

    private int $x1;
    private int $y1;
    private int $z1;

    private int $x2;
    private int $y2;
    private int $z2;

    public function canPlaceBlocks(): ?bool
    {
        return $this->blockPlace;
    }

    public function setBlockPlace(?bool $blockPlace): void
    {
        $this->blockPlace = $blockPlace;
    }

    public function canBreakBlocks(): ?bool
    {
        return $this->blockBreak;
    }

    public function setBlockBreak(?bool $blockBreak): void
    {
        $this->blockBreak = $blockBreak;
    }

    public function canInteract(): ?bool
    {
        return $this->blockInteract;
    }

    public function setBlockInteract(?bool $value): void
    {
        $this->blockInteract = $value;
    }

    public function getInteractCheckType(): string
    {
        return $this->interactCheckType;
    }

    public function setInteractCheckType(string $value): void
    {
        $this->interactCheckType = $value;
    }

    public function canEntitiesBeDamaged(): ?bool
    {
        return $this->entityDamage;
    }

    public function setEntityDamageFlag(?bool $value): void
    {
        $this->entityDamage = $value;
    }

    public function getEffects(): array
    {
        return $this->effects;
    }

    public function setEffects(array $effects): void
    {
        $this->effects = $effects;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getWorld(): string
    {
        return $this->world;
    }

    public function setWorld(string $world): void
    {
        $this->world = $world;
    }

    public function getX1(): int
    {
        return $this->x1;
    }

    public function setX1(int $x1): void
    {
        $this->x1 = $x1;
    }

    public function getY1(): int
    {
        return $this->y1;
    }

    public function setY1(int $y1): void
    {
        $this->y1 = $y1;
    }

    public function getZ1(): int
    {
        return $this->z1;
    }

    public function setZ1(int $z1): void
    {
        $this->z1 = $z1;
    }

    public function getX2(): int
    {
        return $this->x2;
    }

    public function setX2(int $x2): void
    {
        $this->x2 = $x2;
    }

    public function getY2(): int
    {
        return $this->y2;
    }

    public function setY2(int $y2): void
    {
        $this->y2 = $y2;
    }

    public function getZ2(): int
    {
        return $this->z2;
    }

    public function setZ2(int $z2): void
    {
        $this->z2 = $z2;
    }

    public function isInside(Position $position): bool
    {
        $x = (int) $position->getX();
        $y = (int) $position->getY();
        $z = (int) $position->getZ();
        $world = $position->getWorld()->getFolderName();

        return
            $x >= min($this->getX1(), $this->getX2()) && $x <= max($this->getX1(), $this->getX2()) &&
            $y >= min($this->getY1(), $this->getY2()) && $y <= max($this->getY1(), $this->getY2()) &&
            $z >= min($this->getZ1(), $this->getZ2()) && $z <= max($this->getZ1(), $this->getZ2()) &&
            $world === $this->getWorld();
    }
}
