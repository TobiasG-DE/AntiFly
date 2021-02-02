<?php

namespace tobias\antifly;
class FlagManager
{
    private array $flags = [];

    public function update(string $playerName, int $flagId, $value): void
    {

        if (isset($this->flags[$playerName])) {
            $this->flags[$playerName][$flagId] = $value;
        } else {
            $this->flags[$playerName] = [
                $flagId => $value
            ];
        }
    }

    public function get(string $playerName, int $flagId)
    {
        if (!isset($this->flags[$playerName]))
            return null;

        if (isset($this->flags[$playerName][$flagId])) {
            return $this->flags[$playerName][$flagId];
        }

        return null;
    }

    public function increase(string $playerName, int $flagId, int $factor = 1): int
    {
        $currentValue = $this->get($playerName, $flagId);
        if (($currentValue + $factor) <= 0) {
            $this->update($playerName, $flagId, 0);
            return 0;
        }

        if ($currentValue === null) {
            $this->update($playerName, $flagId, $factor);
            return $factor;
        } else {
            $this->update($playerName, $flagId, $currentValue + $factor);
            return $currentValue + $factor;
        }
    }

    public function decrease(string $playerName, int $flagId): int
    {
        return $this->increase($playerName, $flagId, -1);
    }

    public function reset(string $playerName): void
    {
        if (isset($this->flags[$playerName])) {
            unset($this->flags[$playerName]);
        }
    }
}