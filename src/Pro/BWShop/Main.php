<?php

declare(strict_types=1);

namespace Pro\BWShop;

use muqsit\invmenu\InvMenu;
use gameapi\API;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\inventories\BaseFakeInventory;
use pocketmine\entity\Villager;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Potion;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Server;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;

class Main extends PluginBase implements Listener {

	const PREFIX = TextFormat::WHITE."SHOP".TextFormat::GRAY." ->".TextFormat::YELLOW." ";
	const BRONZE = "Brick";
 	const SILVER = "Iron";
  	const GOLD = "Gold";
	private $menu;
	
	public function onEnable() : void{
		 if(!InvMenuHandler::isRegistered()){
		InvMenuHandler::register($this);
		}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getLogger()->info(" Enabled ");
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{
		switch($cmd->getName()){
			case "bwshop":
				if($sender instanceof Player){
					$this->openShop($sender);
				}
			break;
		}
		return true;
	}

	public function openShop(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName(TextFormat::RED . "Bed" . TextFormat::WHITE . "Wars " . TextFormat::RESET . "Shop")->readonly();
        $menu->getInventory()->setContents([
            Item::get(ItemIds::CHAIN_CHESTPLATE)->setCustomName("Armor"),
            Item::get(ItemIds::SANDSTONE)->setCustomName("Blocks"),
            Item::get(ItemIds::STONE_PICKAXE)->setCustomName("Pickaxes"),
            Item::get(ItemIds::STONE_SWORD)->setCustomName("Weapons"),
            Item::get(ItemIds::BOW)->setCustomName("Bows"),
            Item::get(ItemIds::POTION)->setCustomName("Other")
        ]);
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
            switch ($itemClicked->getId()) {
                case ItemIds::CHAIN_CHESTPLATE:
                    $this->openShopArmor($player);
                    break;
                case ItemIds::SANDSTONE:
                    $this->openShopBlock($player);
                    break;
                case ItemIds::STONE_PICKAXE:
                    $this->openShopPickaxe($player);
                    break;
                case ItemIds::STONE_SWORD:
                    $this->openShopWeapons($player);
                    break;
                case ItemIds::BOW:
                    $this->openShopBow($player);
                    break;
                case ItemIds::POTION:
                    $this->openShopSpecial($player);
                    break;
            }
            return true;
        });
        $menu->send($player);
    }

	private function openShopArmor(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName("Armor shop")->readonly();
        $menu->setInventoryCloseListener(function(Player $player, BaseFakeInventory $inventory) : void{ $player->removeWindow($inventory) });
        //enchanted and colored items
        $lc = $this->generateShopItem(Item::get(ItemIds::LEATHER_CAP), 1, 2 * 1, self::BRONZE);
        $lp = $this->generateShopItem(Item::get(ItemIds::LEATHER_PANTS), 1, 2 * 1, self::BRONZE);
        $lb = $this->generateShopItem(Item::get(ItemIds::LEATHER_BOOTS), 1, 2 * 1, self::BRONZE);
        $c1 = $this->generateShopItem(Item::get(ItemIds::CHAIN_CHESTPLATE), 1, 2 * 1, self::SILVER);
        $c1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION)));
        $c1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
        $c2 = $this->generateShopItem(Item::get(ItemIds::CHAIN_CHESTPLATE), 1, 4 * 1, self::SILVER);
        $c2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), 2));
        $c2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));

        $menu->getInventory()->setContents([
            $lc,
            $lp,
            $lb,
            $c1,
            $c2
        ]);
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
            $this->buyItem($itemClicked, $player);
            return true;
        });
        $menu->send($player);
    }

    private function openShopBlock(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName("Block shop")->readonly();
        $menu->setInventoryCloseListener(function(Player $player, BaseFakeInventory $inventory) : void{ $player->removeWindow($inventory) });
        $menu->getInventory()->setContents([
            $this->generateShopItem(Item::get(ItemIds::SANDSTONE), 4, 0.5 * 4, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::SANDSTONE), 16, 0.5 * 16, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::SANDSTONE), 32, 0.5 * 32, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::SANDSTONE), 64, 0.5 * 64, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::END_STONE), 1, 8 * 1, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::END_STONE), 4, 8 * 4, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::END_STONE), 16, 8 * 16, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::END_STONE), 32, 8 * 32, self::BRONZE)
        ]);
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
            $this->buyItem($itemClicked, $player);
            return true;
        });
        $menu->send($player);
    }

    private function openShopPickaxe(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName("Pickaxe shop")->readonly();
        $menu->setInventoryCloseListener(function(Player $player, BaseFakeInventory $inventory) : void{ $player->removeWindow($inventory) });
        //enchanted items
        $ipe1 = $this->generateShopItem(Item::get(ItemIds::IRON_PICKAXE), 1, 8, self::SILVER);
        $ipe1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY)));
        $gpe2 = $this->generateShopItem(Item::get(ItemIds::GOLD_PICKAXE), 1, 4, self::GOLD);
        $gpe2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 2));

        $menu->getInventory()->setContents([
            $this->generateShopItem(Item::get(ItemIds::STONE_PICKAXE), 1, 16, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::IRON_PICKAXE), 1, 4, self::SILVER),
            $ipe1,
            $gpe2
        ]);
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
            $this->buyItem($itemClicked, $player);
            return true;
        });
        $menu->send($player);
    }

    private function openShopWeapons(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName("Weapon shop")->readonly();
        $menu->setInventoryCloseListener(function(Player $player, BaseFakeInventory $inventory) : void{ $player->removeWindow($inventory) });
        //enchanted items
        $kbs = $this->generateShopItem(Item::get(ItemIds::STICK), 1, 8, self::BRONZE);
        $kbs->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::KNOCKBACK)));

        $gs1 = $this->generateShopItem(Item::get(ItemIds::GOLD_SWORD), 1, 2, self::SILVER);
        $gs1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));

        $gs2 = $this->generateShopItem(Item::get(ItemIds::GOLD_SWORD), 1, 4, self::SILVER);
        $gs2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
        $gs2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS)));;

        $gs3 = $this->generateShopItem(Item::get(ItemIds::GOLD_SWORD), 1, 8, self::SILVER);
        $gs3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
        $gs3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS), 2));;

        $is1 = $this->generateShopItem(Item::get(ItemIds::IRON_SWORD), 1, 4, self::GOLD);
        $is1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
        $is1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS)));

        $menu->getInventory()->setContents([
            $kbs,
            $gs1,
            $gs2,
            $gs3,
            $is1
        ]);
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
            $this->buyItem($itemClicked, $player);
            return true;
        });
        $menu->send($player);
    }

    private function openShopBow(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName("Bow shop")->readonly();
        $menu->setInventoryCloseListener(function(Player $player, BaseFakeInventory $inventory) : void{ $player->removeWindow($inventory) });
        //enchanted items
        $b1 = $this->generateShopItem(Item::get(ItemIds::BOW), 1, 4, self::GOLD);
        $b1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));

        $b2 = $this->generateShopItem(Item::get(ItemIds::BOW), 1, 8, self::GOLD);
        $b2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
        $b2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::POWER)));

        $b3 = $this->generateShopItem(Item::get(ItemIds::BOW), 1, 16, self::GOLD);
        $b3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
        $b3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::INFINITY)));

        $menu->getInventory()->setContents([
            $b1,
            $b2,
            $b3,
            $this->generateShopItem(Item::get(ItemIds::ARROW), 4, 0.25 * 4, self::SILVER),
            $this->generateShopItem(Item::get(ItemIds::ARROW), 8, 0.25 * 8, self::SILVER),
            $this->generateShopItem(Item::get(ItemIds::ARROW), 16, 0.25 * 16, self::SILVER)
        ]);
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
            $this->buyItem($itemClicked, $player);
            return true;
        });
        $menu->send($player);
    }

    private function openShopSpecial(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName("Special shop")->readonly();
        $menu->setInventoryCloseListener(function(Player $player, BaseFakeInventory $inventory) : void{ $player->removeWindow($inventory) });
        $menu->getInventory()->setContents([
            $this->generateShopItem(Item::get(ItemIds::ENDER_PEARL), 1, 4 * 1, self::GOLD),
            $this->generateShopItem(Item::get(ItemIds::TNT), 1, 16 * 1, self::SILVER),
            $this->generateShopItem(Item::get(ItemIds::POTION, Potion::STRONG_SWIFTNESS), 1, 4 * 1, self::SILVER),
            $this->generateShopItem(Item::get(ItemIds::POTION, Potion::STRONG_STRENGTH), 1, 2 * 1, self::GOLD),
            $this->generateShopItem(Item::get(ItemIds::SPLASH_POTION, Potion::SLOWNESS), 1, 4 * 1, self::SILVER),
            $this->generateShopItem(Item::get(ItemIds::SPLASH_POTION, Potion::WEAKNESS), 1, 2 * 1, self::GOLD),
            $this->generateShopItem(Item::get(ItemIds::SPLASH_POTION, Potion::POISON), 1, 2 * 1, self::GOLD),
            $this->generateShopItem(Item::get(ItemIds::BUCKET, 1), 1, 2 * 1, self::SILVER),
        ]);
        $menu->setListener(function (Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
            $this->buyItem($itemClicked, $player);
            return true;
        });
        $menu->send($player);
    }
	
	private function subToMainShop(): \Closure
    {
        return function (Player $player, BaseFakeInventory $inventory) {
            $player->removeWindow($inventory);
            $this->openShop($player);
        };
    }
	
	    private function generateShopItem(Item $item, int $size, int $value, string $valueType = self::GOLD): Item
    {
        $item->setCount($size);
        $item->setNamedTag($value . "x " . $valueType);
        return $item;
    }

    private function buyItem(Item $item, Player $player): bool
    {
        [$value, $valueType] = explode("x ", $item->getNamedTag()[0] ?? "0x " . self::GOLD);
        $value = intval($value);
        if ($value < 1) return false;
        $item = $item->setNamedTag();
        switch($valueType) {
            case self::BRONZE:
                $id = ItemIds::BRICK;
                break;
            case self::SILVER:
                $id = ItemIds::IRON_INGOT;
                break;
            case self::GOLD:
                $id = ItemIds::GOLD_INGOT;
                break;
            default:
                $player->sendTip("Error");
        }
        $payment = Item::get($id, 0, $value);
        if ($player->getInventory()->contains($payment)) {
            $player->getInventory()->removeItem($payment);
            $player->getInventory()->addItem($item);
            return true;
        }
        return false;
    }
	

}
