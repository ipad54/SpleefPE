<?php

namespace sp\task;

use pocketmine\scheduler\Task;

use pocketmine\level\Level;
use pocketmine\Server;

use sp\Main;

class StartTask extends Task
{
    public $p, $lvl, $time, $start;

    public function __construct(Main $plugin, string $lvl)
    {
        $this->p = $plugin;
        $this->lvl = $lvl;
        $this->time = 30;
        $this->start = 0;
    }

    public function onRun(int $currentTick): void
    {
        if (count($this->p->getServer()->getLevelByName($this->lvl)->getPlayers()) >= 2) {
            $this->time--;
            foreach ($this->p->getServer()->getLevelByName($this->lvl)->getPlayers() as $pl) {
                $pl->sendTip(str_replace("{COUNT}", $this->time, $this->p->getMsg()->get("timer_start")));
                if ($this->time <= 0) {
                    $this->p->setGame($this->lvl, true);
                    $this->startg();

                    $this->p->getScheduler()->cancelTask($this->getTaskId());

                }
            }

        } else {
            $this->p->getScheduler()->cancelTask($this->getTaskId());
        }
    }

    public function startg(): void
    {
        if ($this->start == 0) {
            $this->start++;
            $this->p->startGame($this->lvl);
        }
    }
}
