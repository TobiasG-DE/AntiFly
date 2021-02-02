<?php
namespace tobias\antifly;
use pocketmine\plugin\PluginBase;
use tobias\antifly\Listener\AntiFlyListener;

class AntiFly extends PluginBase
{
    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents(new AntiFlyListener(), $this);
    }
}