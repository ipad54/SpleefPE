<?php

namespace sp\task;

use pocketmine\scheduler\Task;

use pocketmine\level\Level;
use pocketmine\Server;

use sp\Main;

class SignTask extends Task{
  public $p;

public function __construct(Main $plugin) {
$this->p = $plugin;
}
public function onRun(int $currentTick) : void {
$this->p->sign();
}
}
