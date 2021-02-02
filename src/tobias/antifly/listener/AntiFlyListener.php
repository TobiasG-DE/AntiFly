<?php
declare(strict_types=1);

namespace tobias\antifly\Listener;


use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class AntiFlyListener implements Listener
{

    public const FLAG_TRESHOLD = 20;

    public const PLAYER_LAST_Y = 1;

    public const OFFSET_PLAYER = 200;
    public const PLAYER_SAME_Y = self::OFFSET_PLAYER + 1;
    public const PLAYER_HIGHER = self::OFFSET_PLAYER + 2;

    private $flags = [];

    public function onReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        $session = $event->getOrigin();
        $player = $session->getPlayer();

        if ($packet instanceof MovePlayerPacket) {

            if ($player->getPosition()->equals($packet->position)) return; // Head Rotation
            $ground = $packet->onGround; // We technically cannot trust this as it's sent by the client
            if ($ground) {
                $this->update($player->getName(), self::PLAYER_SAME_Y, 0);
                $this->update($player->getName(), self::PLAYER_HIGHER, 0);
                return;
            }

            $y = round($packet->position->y - $player->getEyeHeight(), 4);
            $oldY = $this->get($player->getName(), self::PLAYER_LAST_Y);
            if ($oldY === null) {
                $oldY = round($player->getPosition()->getY(), 4);
            }

            if ($packet->teleportCause === 0) {
                $this->update($player->getName(), self::PLAYER_LAST_Y, $y);
                if ($y === $oldY) {
                    $value = $this->increase($player->getName(), self::PLAYER_SAME_Y);
                    if ($value > self::FLAG_TRESHOLD) {
                        $player->kick("same-y");
                        return;
                    }
                }
            }
            $rising = ($oldY < $y); // This triggers with jump boost 20+
            if ($rising) {
                $val = $this->increase($player->getName(), self::PLAYER_HIGHER);
                if ($val > self::FLAG_TRESHOLD) {
                    $player->kick("Rising-Fly");
                }
            } else {
                $this->decrease($player->getName(), self::PLAYER_HIGHER);
            }
        }
    }

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
        if ($currentValue === null) {
            $this->update($playerName, $flagId, $factor);
            $newValue = $factor;
        } else {
            $this->update($playerName, $flagId, $currentValue + $factor);
            $newValue = $currentValue + $factor;
        }
        return $newValue;
    }

    public function decrease(string $playerName, int $flagId)
    {
        $this->increase($playerName, $flagId, -1);
    }

}