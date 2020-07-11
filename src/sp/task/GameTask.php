<?php

namespace sp\task;

use pocketmine\scheduler\Task;

use pocketmine\level\Level;
use pocketmine\Server;

use sp\Main;

class GameTask extends Task{

public function __construct(Main $plugin, string $lvl) {
$this->p = $plugin;
$this->lvl = $lvl;
$this->time = 300;
}
public function onRun(int $currentTick) : void {
$this->time--;
if(count($this->p->getServer()->getLevelByName($this->lvl)->getPlayers()) <= 1){
$this->p->getScheduler()->cancelTask($this->getTaskId());
}
if($this->time <= 1){
$this->p->nichya($this->lvl);

} else {
foreach($this->p->getServer()->getLevelByName($this->lvl)->getPlayers() as $pl) {
$c = count($this->p->getServer()->getLevelByName($this->lvl)->getPlayers());
$pl->sendTip(str_replace("{COUNT}", $c, $this->p->getMsg()->get("player_count")));
}
$this->p->getWinner($this->lvl);
$this->p->fix($this->lvl);

}
}
} 
