<?php

namespace sp;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat as TE;
use pocketmine\utils\Config;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\tile\Sign;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\tile\Chest;
use pocketmine\inventory\ChestInventory;
use onebone\economyapi\EconomyAPI;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\entity\Effect;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\block\Air;
use pocketmine\scheduler\Task;
use sp\task\StartTask;
use sp\task\GameTask;
use sp\ResetMap;
use sp\task\SignTask;
use pocketmine\event\player\PlayerRespawnEvent;

class Main extends PluginBase implements Listener{
public $prefix = TE::GRAY . "[" . TE::AQUA . TE::BOLD . "" . TE::RED . "Spleef" . TE::RESET . TE::GRAY . "]";
public $game = [];
public $levels = array();
public $msg, $cfg, $setup, $level;
public function onEnable() {
if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") !== null){
$this->getServer()->getPluginManager()->registerEvents($this, $this);
$this->saveResource("settings.yml");
$this->saveResource("config.yml");

$this->getScheduler()->scheduleRepeatingTask(new SignTask($this), 20 * 5);
$this->cfg = new Config($this->getDataFolder()."config.yml", Config::YAML);
$this->msg = new Config($this->getDataFolder()."settings.yml", Config::YAML);
if($this->cfg->get("arenas")){
foreach($this->cfg->get("arenas") as $a) {
$this->game[$a] = false;
}
$this->levels = $this->cfg->get("arenas");
}
} else {
$this->getLogger()->info("EconomyAPI not found!");
$this->getServer()->getPluginManager()->disablePlugin($this);
}
}
public function onBreak(BlockBreakEvent $e) {
$p = $e->getPlayer();
$b = $e->getBlock();
$lvl = $p->getLevel()->getName();
if(in_array($lvl, $this->levels)) {
if(!$this->game[$lvl]) {
$e->setCancelled();
return false;
}
if($b->getId() !== 80){
$e->setCancelled();
return false;
}
}
}


public function Join(PlayerLoginEvent $e) {
$p=$e->getPlayer();
$lvl = $p->getLevel()->getName();
if(in_array($lvl, $this->levels)) {
$p->teleport(new Position($this->getServer()->getDefaultLevel()->getSafeSpawn()->x, $this->getServer()->getDefaultLevel()->getSafeSpawn()->y, $this->getServer()->getDefaultLevel()->getSafeSpawn()->z, $this->getServer()->getDefaultLevel()));
return true;
}
}
public function onDeath(PlayerDeathEvent $e) {
$p = $e->getPlayer();
$lvl = $p->getLevel()->getName();
if(in_array($lvl, $this->levels)) {
$e->setDrops([]);
$p->teleport(new Position($this->getServer()->getDefaultLevel()->getSafeSpawn()->x, $this->getServer()->getDefaultLevel()->getSafeSpawn()->y, $this->getServer()->getDefaultLevel()->getSafeSpawn()->z, $this->getServer()->getDefaultLevel()));

return true;
}
}

public function onCommand(CommandSender $s, Command $cmd, string $label, array $args) : bool{
if($cmd->getName() !== "spleef"){
return false;
}
if(!$s instanceof Player){
$s->sendMessage("Run this command on game");
return false;
}
if(count($args) < 2){
$s->sendMessage("/spleef make Arena Name");
return false;
}
$level = $args[1];
if(!$this->getServer()->loadlevel($level)) {
$s->sendMessage($this->msg->get("error_missing_world"));
return false;
}
$this->getServer()->loadlevel($level);
$lvl = $this->getServer()->getLevelByName($level);
$s->teleport(new Position($lvl->getSafeSpawn()->x, $lvl->getSafeSpawn()->y, $lvl->getSafeSpawn()->z, $lvl));
$this->setup[$s->getName()] = 1;
$s->sendMessage($this->msg->get("touch_spawn_point"));
return true;
}
}
public function SetupArena(PlayerInteractEvent $e) {
$p = $e->getPlayer();
$s = $p;
if(isset($this->setup[$p->getName()])) {

if($this->setup[$s->getName()] == 1){


$b = $e->getBlock();
$level = $p->getLevel()->getName();
$this->cfg->set($level."Spawn", array($b->getX(), $b->getY() + 1, $b->getZ()));
$this->cfg->save();

$this->level = $s->getLevel()->getName();
if(!$this->cfg->get("arenas")) {

$this->cfg->set("arenas", array($this->level));
$this->cfg->save();
} else {
$array = $this->cfg->get("arenas");
array_push($array, $this->level);
$this->cfg->set("arenas", $array);
$this->cfg->save();
}
$p->sendMessage($this->msg->get("touch_sign"));
$p->teleport(new Position($this->getServer()->getDefaultLevel()->getSafeSpawn()->x, $this->getServer()->getDefaultLevel()->getSafeSpawn()->y, $this->getServer()->getDefaultLevel()->getSafeSpawn()->z, $this->getServer()->getDefaultLevel()));
unset($this->setup[$s->getName()]);
$this->setup[$s->getName()] = 2;



return true;

} elseif($this->setup[$s->getName()] == 2){

$b = $e->getBlock();
$tile = $p->getLevel()->getTile($b);
if($tile instanceof Sign) {
$e->setCancelled();
$p->sendMessage("Registering arena..... ");
$this->zipper($p, $this->level);


$this->cfg->set($this->level."Sign", array($b->getX(), $b->getY(), $b->getZ(), $this->level));
$this->cfg->save();

$tile->setText(TE::AQUA . "§7[ §fJoin §7]",TE::YELLOW  . "0 / 12","§f" . $this->level,$this->prefix);
$p->sendMessage("Arena registered!");
$this->game[$this->level] = false;
unset($this->setup[$s->getName()]);
$this->levels = $this->cfg->get("arenas");
return true;
}
}
return true;
}
public function zipper($player, $name)
        {
        $path = realpath($this->getServer()->getDataPath() . 'worlds/' . $name);
				$zip = new \ZipArchive;
				@mkdir($this->getDataFolder() . 'arenas/', 0755);
				$zip->open($this->getDataFolder() . 'arenas/' . $name . '.zip', $zip::CREATE | $zip::OVERWRITE);
				$files = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($path),
					\RecursiveIteratorIterator::LEAVES_ONLY
				);
                                foreach ($files as $datos) {
					if (!$datos->isDir()) {
						$relativePath = $name . '/' . substr($datos, strlen($path) + 1);
						$zip->addFile($datos, $relativePath);
					}
				}
				$zip->close();
				$player->getServer()->loadLevel($name);
				unset($zip, $path, $files);
        }
public function joinArena(PlayerInteractEvent $e) {
if($e->isCancelled()) {
return false;
}
$p = $e->getPlayer();
if(!$this->cfg->get("arenas")) {
return false;
}
foreach($this->cfg->get("arenas") as $a) {
$sign = $this->cfg->get($a."Sign");
$x = $e->getBlock()->getX();
$y = $e->getBlock()->getY();
$z = $e->getBlock()->getZ();
if($x == $sign[0] && $y == $sign[1] && $z == $sign[2]){
$lvl = $sign[3];

$this->getServer()->loadLevel($sign[3]);

if(count($this->getServer()->getLevelByName($lvl)->getPlayers()) >= 12 || $this->game[$lvl] == true){
$p->sendMessage($this->msg->get("game_start"));
return false;
} else {
$spawn = $this->cfg->get($lvl."Spawn");
$p->teleport(new Position($spawn[0], $spawn[1], $spawn[2], $this->getServer()->getLevelByName($lvl)));
$p->setGamemode(0);
$p->setHealth($p->getMaxHealth());
$p->getInventory()->clearAll();
$this->getServer()->getLevelByName($lvl)->setTime(0);
if(count($this->getServer()->getLevelByName($lvl)->getPlayers()) == 2){
$this->getScheduler()->scheduleRepeatingTask(new StartTask($this, $lvl), 20);

return true;
}
}
}
}
}
public function setGame(string $lvl, bool $start) {
$this->game[$lvl] = $start;
return $start;
}
public function startGame(string $lvl) : void{
$this->getScheduler()->scheduleRepeatingTask(new GameTask($this, $lvl), 20);
foreach($this->getServer()->getLevelByName($lvl)->getPlayers() as $pl) {
$pl->sendMessage($this->msg->get("game_start_1"));
$pl->sendMessage($this->msg->get("game_start_2"));
$pl->getInventory()->addItem(Item::get(277,0,1));
}
}
public function getWinner(string $level) : void {
$lvl = $this->getServer()->getLevelByName($level);
$count = count($lvl->getPlayers());
if($count == 1){
foreach($lvl->getPlayers() as $pl) {
$winner = $pl;
$sp = $this->getServer()->getDefaultLevel();
$winner->teleport(new Position($sp->getSafeSpawn()->x, $sp->getSafeSpawn()->y, $sp->getSafeSpawn()->z, $sp));
$winner->getInventory()->clearAll();

//$api = EconomyAPI::getInstance();
//$money = $this->msg->get("money-reward");
//$api->addMoney($winner, $money);
$this->getResetMap()->reload($lvl);
$this->game[$level] = false;
$winner->addTitle($this->msg->get("victory"));
}
}
}
public function fix(string $level) : void {
$lvl = $this->getServer()->getLevelByName($level);
$count = count($lvl->getPlayers());
if($count <= 0 && $this->game[$level]) {
$this->getResetMap()->reload($lvl);

$this->game[$level] = false;
}
}
public function nichya(string $level) : void {
$lvl = $this->getServer()->getLevelByName($level);
foreach($lvl->getPlayers() as $pl) {
$pl->addTitle($this->msg->get("time_is_over"));
$pl->getInventory()->clearAll();
$sp = $this->getServer()->getDefaultLevel();

$pl->teleport(new Position($sp->getSafeSpawn()->x, $sp->getSafeSpawn()->y, $sp->getSafeSpawn()->z, $sp));
$this->getResetMap()->reload($lvl);
$this->game[$level] = false;
}
}
public function onDamage(EntityDamageEvent $e) {
if($e instanceof EntityDamageByEntityEvent) {
$d = $e->getDamager();
$en = $e->getEntity();
if($en instanceof Player && $d instanceof Player) {
$lvl = $en->getLevel()->getName();
if(in_array($lvl, $this->levels)) {
$e->setCancelled();
return true;
}
}
}
}
public function getResetMap() {
return new ResetMap($this);
}
public function sign() {
foreach($this->levels as $lvl){
if(!$this->cfg->get("arenas")) {
return false;
}
$pos = $this->cfg->get($lvl."Sign");
$this->getServer()->loadLevel($lvl);
$count = count($this->getServer()->getLevelByName($lvl)->getPlayers());
foreach($this->getServer()->getDefaultLevel()->getTiles() as $tile) {
if($tile instanceof Sign) {
if($tile->x == $pos[0] && $tile->y == $pos[1] && $tile->z == $pos[2]){
if($this->game[$lvl]) {

$tile->setText(TE::AQUA . "§7[ §cRunning §7]",TE::YELLOW  . "$count / 12","§f" . $lvl,$this->prefix);
} else {
$tile->setText(TE::AQUA . "§7[ §fJoin §7]",TE::YELLOW  . "$count / 12","§f" . $lvl,$this->prefix);
}
return true;
}
}
}
}
}
public function stopAll() : void {
foreach($this->game as $lvl => $start) {
if($this->game[$lvl]) {
$this->stop($lvl);

}
}
}
public function stop(string $lvl) : void {
$this->getServer()->loadLevel($lvl);
foreach($this->getServer()->getLevelByName($lvl)->getPlayers() as $pl) {
$pl->teleport(new Position($this->getServer()->getDefaultLevel()->getSafeSpawn()->x, $this->getServer()->getDefaultLevel()->getSafeSpawn()->y, $this->getServer()->getDefaultLevel()->getSafeSpawn()->z, $this->getServer()->getDefaultLevel()));
$pl->getInventory()->clearAll();
$pl->setHealth($pl->getMaxHealth());
$this->getResetMap()->reload($this->getServer()->getLevelByName($lvl));
$this->game[$lvl] = false;

}
}
public function onRespawn(PlayerRespawnEvent $e) {
$p = $e->getPlayer();
$lvl = $p->getLevel()->getName();
if(in_array($lvl, $this->levels)) {
$p->teleport(new Position($this->getServer()->getDefaultLevel()->getSafeSpawn()->x, $this->getServer()->getDefaultLevel()->getSafeSpawn()->y, $this->getServer()->getDefaultLevel()->getSafeSpawn()->z, $this->getServer()->getDefaultLevel()));

return true;
}
}
public function onPlace(BlockPlaceEvent $e) {
$p = $e->getPlayer();
$lvl = $p->getLevel()->getName();
if(in_array($lvl, $this->levels)) {
$e->setCancelled();
return true;
} 
}
public function getMsg() : Config{
return $this->msg;
}
public function onDisable(){
$this->stopAll();
}

}
