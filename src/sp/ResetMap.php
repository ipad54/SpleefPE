<?php

namespace sp;

use pocketmine\level\Level;
use sp\Main;

class ResetMap
{
    public $plugin;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    public function reload(Level $lev)
    {
        $name = $lev->getFolderName();
        if ($this->plugin->getServer()->isLevelLoaded($name)) {
            $this->plugin->getServer()->unloadLevel($this->plugin->getServer()->getLevelByName($name));
        }
        $zip = new \ZipArchive;
        $zip->open($this->plugin->getDataFolder() . 'arenas/' . $name . '.zip');
        $zip->extractTo($this->plugin->getServer()->getDataPath() . 'worlds');
        $zip->close();
        unset($zip);
        $this->plugin->getServer()->loadLevel($name);
        return true;
    }
}
