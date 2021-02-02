<?php
namespace tobias\antifly;
use pocketmine\plugin\PluginBase;
use tobias\antifly\Listener\AntiFlyListener;

class AntiFly extends PluginBase
{
    private FlagManager $flagManager;

    public function onEnable(): void
    {
        $this->flagManager = new FlagManager();
        $this->getServer()->getPluginManager()->registerEvents(new AntiFlyListener($this->flagManager), $this);
    }
}