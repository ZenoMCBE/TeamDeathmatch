<?php /* @noinspection PhpDeprecationInspection */

namespace tdm\loaders\childs;

use tdm\loaders\Loader;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ItemFlags;

final class EnchantmentsLoader implements Loader {

    /**
     * @return void
     * @noinspection PhpDeprecationInspection
     */
    public function onLoad(): void {
        EnchantmentIdMap::getInstance()->register(-1, new Enchantment('glow', -1, ItemFlags::ALL, ItemFlags::NONE, 1));
    }

    /**
     * @return void
     */
    public function onUnload(): void {}

}
