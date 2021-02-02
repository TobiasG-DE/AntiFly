<?php
declare(strict_types=1);

namespace tobias\antifly\Listener;


use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use tobias\antifly\FlagManager;

class AntiFlyListener implements Listener
{

    public const SAME_Y_TRESHOLD = 20;
    public const PLAYER_HIGHER_TRESHOLD = 20;

    public const PLAYER_LAST_Y = 1;

    public const OFFSET_PLAYER = 200;
    public const PLAYER_SAME_Y = self::OFFSET_PLAYER + 1;
    public const PLAYER_HIGHER = self::OFFSET_PLAYER + 2;

    private FlagManager $flagManager;

    public function __construct(FlagManager $manager)
    {
        $this->flagManager = $manager;
    }

    public function onReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        $session = $event->getOrigin();
        $player = $session->getPlayer();

        if ($packet instanceof MovePlayerPacket) {

            if ($player->getPosition()->equals($packet->position)) return; // Head Rotation
            if($packet->teleportCause !== 0) return; // Ignore teleports
            
            $ground = $packet->onGround; // We technically cannot trust this as it's sent by the client
            if ($ground) { // Reset the fly values when the player touches the ground
                $this->flagManager->update($player->getName(), self::PLAYER_SAME_Y, 0);
                $this->flagManager->update($player->getName(), self::PLAYER_HIGHER, 0);
                return;
            }

            // Calculating of the current y and last-y
            $y = round($packet->position->y - $player->getEyeHeight(), 4);
            $oldY = $this->flagManager->get($player->getName(), self::PLAYER_LAST_Y);
            if ($oldY === null) {
                $oldY = round($player->getPosition()->getY(), 4);
            }

            // Start same-y check
            $this->flagManager->update($player->getName(), self::PLAYER_LAST_Y, $y);
            if ($y === $oldY) {
                $value = $this->flagManager->increase($player->getName(), self::PLAYER_SAME_Y);
                if ($value > self::SAME_Y_TRESHOLD) {
                    $player->kick("§cYou were kicked: same-y", "AntiCheat: Same-y");
                }
                return;
            }
            // End same-y check


            // Start rising-fly check
            $rising = ($oldY < $y);
            if ($rising) {
                $val = $this->flagManager->increase($player->getName(), self::PLAYER_HIGHER);
                if ($val > self::PLAYER_HIGHER_TRESHOLD) {
                    $player->kick("§cYou were kicked: rising-fly", "AntiCheat: Rising-Fly");
                }
            } else {
                $this->flagManager->decrease($player->getName(), self::PLAYER_HIGHER);
            }
            // End rising-fly check
        }
    }

    // Usually I would add another event handler here to block clients from setting themselves in AdventureSettingsPacket.ALLOW_FLIGHT,
    // but as Horion doesn't work I don't have a reliable way to test this

    public function onQuit(PlayerQuitEvent $event)
    {
        $this->flagManager->reset($event->getPlayer()->getName());
    }


}