<?php

namespace presentkim\dustbin\inventory;

use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\inventory\{
  BaseInventory, CustomInventory
};
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\{
  CompoundTag, StringTag
};
use pocketmine\network\mcpe\protocol\{
  UpdateBlockPacket, BlockEntityDataPacket, ContainerOpenPacket
};
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\tile\Spawnable;
use presentkim\dustbin\DustBin;

class DustBinInventory extends CustomInventory{

    /** @var DustBinInventory[] */
    public static $bins = [];

    /** CompoundTag */
    private $nbt;

    public function __construct(){
        parent::__construct(new Vector3(), [], $this->getDefaultSize(), null);

        $this->nbt = new CompoundTag('', [
          new StringTag('id', 'Chest'),
          new StringTag('CustomName', DustBin::getInstance()->getLanguage()->translate('dustbin.name')),
        ]);
    }

    /**
     * @param Player $who
     */
    public function onOpen(Player $who) : void{
        BaseInventory::onOpen($who);

        $this->holder = $who->subtract(0, 3, 0)->floor();
        if ($this->holder->y < 0) {
            $this->holder->y = 0;
        }

        $pk = new UpdateBlockPacket();
        $pk->blockId = Block::CHEST;
        $pk->blockData = 0;
        $pk->x = $this->holder->x;
        $pk->y = $this->holder->y;
        $pk->z = $this->holder->z;
        $who->sendDataPacket($pk);


        $this->nbt->setInt('x', $this->holder->x);
        $this->nbt->setInt('y', $this->holder->y);
        $this->nbt->setInt('z', $this->holder->z);

        $pk = new BlockEntityDataPacket();
        $pk->x = $this->holder->x;
        $pk->y = $this->holder->y;
        $pk->z = $this->holder->z;
        $pk->namedtag = (new NetworkLittleEndianNBTStream())->write($this->nbt);
        $who->sendDataPacket($pk);


        $pk = new ContainerOpenPacket();
        $pk->type = WindowTypes::CONTAINER;
        $pk->entityUniqueId = -1;
        $pk->x = $this->holder->x;
        $pk->y = $this->holder->y;
        $pk->z = $this->holder->z;
        $pk->windowId = $who->getWindowId($this);
        $who->sendDataPacket($pk);

        $this->sendContents($who);
    }

    /**
     * @param Player $who
     */
    public function onClose(Player $who) : void{
        BaseInventory::onClose($who);

        $block = $who->getLevel()->getBlock($this->holder);

        $pk = new UpdateBlockPacket();
        $pk->x = $this->holder->x;
        $pk->y = $this->holder->y;
        $pk->z = $this->holder->z;
        $pk->blockId = $block->getId();
        $pk->blockData = $block->getDamage();
        $who->sendDataPacket($pk);

        $tile = $who->getLevel()->getTile($this->holder);
        if ($tile instanceof Spawnable) {
            $who->sendDataPacket($tile->createSpawnPacket());
        }
        $this->clearAll();
    }

    /** @return string */
    public function getName() : string{
        return "DustBin";
    }

    /** @return int */
    public function getDefaultSize() : int{
        return 27;
    }

    /** @return int */
    public function getNetworkType() : int{
        return WindowTypes::CONTAINER;
    }
}
