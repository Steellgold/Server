<?php

namespace Steellg0ld\Core\listeners;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\ServerToClientHandshakePacket;
use pocketmine\Server;
use Steellg0ld\Core\forms\EnchantUI;
use Steellg0ld\Core\forms\NaviguateUI;
use Steellg0ld\Core\forms\SettingsUI;
use Steellg0ld\Core\forms\SpellsBookUI;
use Steellg0ld\Core\games\Combat;
use Steellg0ld\Core\managers\Ranks;
use Steellg0ld\Core\Player;
use Steellg0ld\Core\Plugin;

class LPlayers implements Listener{
    /**
     * @param PlayerCreationEvent $event
     * Attribution de la classe personnalisé au joueur
     */
    public function onCreate(PlayerCreationEvent $event){
        $event->setPlayerClass(Player::class);
    }

    /**
     * @param PlayerJoinEvent $event
     * Evenements lors ce que le joueur ce connecte
     */
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $event->setJoinMessage(" ");
        Server::getInstance()->broadcastTip(Plugin::BASE_COLOR." + ".Plugin::SECOND_COLOR.$player->getName().Plugin::BASE_COLOR." +");
        if(!$player instanceof Player) return;

        if(!$player->hasPlayedBefore()){
            $player->dataCreation();
            Server::getInstance()->broadcastMessage(Plugin::PREFIX . Plugin::SECOND_COLOR . " " . $player->getName() . " c'est connecté(e) pour la première fois !");
        }

        $player->setScale($player->getSettings()["size"]);
        $player->cooldown_spell = time();
        $player->cooldown = time();
        $player->teleportTo("world");
        $player->getInventory()->setContents([]);
        $player->getInventory()->setItem(4,Item::get(345));
        $player->getInventory()->setItem(1,Item::get(1002));
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        $event->setQuitMessage("");
        Server::getInstance()->broadcastTip(Plugin::ERROR_COLOR." + ".Plugin::SECOND_COLOR.$player->getName().Plugin::ERROR_COLOR." +");
        if(!$player instanceof Player) return;
        $inv = $player->getOffhandInventory();
        $item = $inv->getItemInOffhand();
        $player->namedtag->setTag($item->nbtSerialize(-1, "offhand"));
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event){
        $player = $event->getPlayer();
        if(!$player instanceof Player) return;
        $message = $event->getMessage();
        $event->setCancelled();
        if($player->getGame() === "NONE"){
            Server::getInstance()->broadcastMessage(str_replace(array("{NAME}", "{MESSAGE}"), array($player->getName(), $message), Ranks::RANKS[$player->getStats()["rank"]]));
        }
    }

     /**
     * @param PlayerItemHeldEvent $event
     * Annuler les déplacements d'items dans l'inventaire lors ce qu'il est au spawn
     */
    public function onHeld(PlayerItemHeldEvent $event){
        $player = $event->getPlayer();
        if(!$player instanceof Player) return;
        if(!$player->getLevel() === Server::getInstance()->getLevelByName("world")) return;
    }

    /**
     * @param PlayerInteractEvent $event
     * Intéraction avec les items de la barre d'inventaire
     */
    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        if (!$player instanceof Player) return;
        if ($player->cooldown < time()) {
            $player->cooldown = time() + 1;
            if (in_array($event->getItem()->getId(), [345, 1001, 1002])) {
                $event->setCancelled();
                if (!$player->getLevel() === Server::getInstance()->getLevelByName("world")) return;

                switch ($event->getItem()->getId()) {
                    case 345:
                        NaviguateUI::openCompassUI($player);
                        break;
                    case 1001:
                        if ($player->getGame() !== Combat::IDENTIFIER) return;
                        SpellsBookUI::openBook($player);
                        break;
                    case 1002:
                        SettingsUI::openSettings($player);
                        break;
                }
            } elseif (in_array($event->getBlock()->getId(), [116])) {
                $event->setCancelled();
                switch ($event->getBlock()->getId()) {
                    case 116:
                        if ($player->getGame() !== Combat::IDENTIFIER) return;
                        EnchantUI::openEnchantUI($player);
                        break;
                }
            }
        }
    }
}