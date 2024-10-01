<?php

namespace LockStorage;

use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Block;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;

class Main extends PluginBase implements Listener {

    private Config $lockedBlocks;

    public function onEnable(): void {
        @mkdir($this->getDataFolder());
        $this->lockedBlocks = new Config($this->getDataFolder() . "locked_blocks.json", Config::JSON);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable(): void {
        $this->lockedBlocks->save();
    }

    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if ($this->isStorageBlock($block)) {
            $pos = $this->blockToString($block);
            $this->lockedBlocks->set($pos, $player->getUniqueId()->toString());
            $this->lockedBlocks->save();
            $player->sendMessage("This storage block has been locked to you.");
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $pos = $this->blockToString($block);

        if ($this->lockedBlocks->exists($pos)) {
            $ownerUuid = $this->lockedBlocks->get($pos);
            if ($ownerUuid !== $player->getUniqueId()->toString()) {
                $event->cancel();
                $player->sendMessage("You can't break this block. It belongs to someone else.");
            } else {
                $this->lockedBlocks->remove($pos);
                $this->lockedBlocks->save();
                $player->sendMessage("You broke your locked block.");
            }
        }
    }

    public function onInventoryOpen(InventoryOpenEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getInventory()->getHolder();

        if ($block instanceof Block) {
            $pos = $this->blockToString($block);

            if ($this->lockedBlocks->exists($pos)) {
                $ownerUuid = $this->lockedBlocks->get($pos);
                if ($ownerUuid !== $player->getUniqueId()->toString()) {
                    $event->cancel();
                    $player->sendMessage("You can't open this storage. It belongs to someone else.");
                }
            }
        }
    }

    private function isStorageBlock(Block $block): bool {
        return in_array($block->getId(), [
            BlockLegacyIds::CHEST,
            BlockLegacyIds::TRAPPED_CHEST,
            BlockLegacyIds::BARREL
        ]);
    }

    private function blockToString(Block $block): string {
        $pos = $block->getPosition();
        return "{$pos->getFloorX()}:{$pos->getFloorY()}:{$pos->getFloorZ()}:{$block->getPosition()->getWorld()->getFolderName()}";
    }
}
