<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace tdm\managers;

use Closure;
use InvalidArgumentException;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use ReflectionException;
use ReflectionProperty;
use tdm\items\CustomItems;
use tdm\utils\Serializable;

final class CustomItemManager {

    use SingletonTrait;

    /**
     * @return void
     * @throws ReflectionException
     */
    public function registerAll(): void {
        foreach (CustomItems::getAll() as $item) {
            $this->registerItem($item);
        }
    }

    /**
     * @param Item $item
     * @return void
     * @throws ReflectionException
     */
    private function registerItem(Item $item): void {
        $id = "minecraft:" . strtolower(str_replace(" ", "_", trim($item->getVanillaName())));
        if ($item instanceof Serializable) {
            $deserializer = $item->deserializer();
            $serializer = $item->serialize();
        } else {
            $deserializer = fn() => clone $item;
            $serializer = fn() => new SavedItemData($id);
        }
        self::registerDeserializerItem($id, $deserializer);
        self::registerSerializerItem($item, $serializer);
        $this->overrideItem($item);
    }

    /**
     * @throws ReflectionException
     * @noinspection PhpExpressionResultUnusedInspection
     */
    private function registerDeserializerItem(string $id, Closure $deserializer): void {
        $instance = GlobalItemDataHandlers::getDeserializer();
        try {
            $instance->map($id, $deserializer);
        } catch (InvalidArgumentException) {
            $deserializerProperty = new ReflectionProperty($instance, "deserializers");
            $deserializerProperty->setAccessible(true);
            $value = $deserializerProperty->getValue($instance);
            $value[$id] = $deserializer;
            $deserializerProperty->setValue($instance, $value);
        }
    }

    /**
     * @throws ReflectionException
     * @noinspection PhpExpressionResultUnusedInspection
     */
    private function registerSerializerItem(Item $item, Closure $serializer): void {
        $instance = GlobalItemDataHandlers::getSerializer();
        try {
            $instance->map($item, $serializer);
        } catch (InvalidArgumentException) {
            $serializerProperty = new ReflectionProperty($instance, "itemSerializers");
            $serializerProperty->setAccessible(true);
            $value = $serializerProperty->getValue($instance);
            $value[$item->getTypeId()] = $serializer;
            $serializerProperty->setValue($instance, $value);
        }
    }

    /**
     * @param Item $item
     * @param array|null $aliases
     * @return void
     */
    private function overrideItem(Item $item, ?array $aliases = null): void {
        if (is_null($aliases)) {
            $aliases = StringToItemParser::getInstance()->lookupAliases($item);
            $newAliase = CustomItems::getAliases($item->getTypeId());
            if (!in_array($newAliase, $aliases)) {
                $aliases[] = $newAliase;
            }
        }
        foreach ($aliases as $alias) {
            StringToItemParser::getInstance()->override($alias, fn() => clone $item);
        }
        if (CreativeInventory::getInstance()->contains($item)) {
            CreativeInventory::getInstance()->remove($item);
        }
        CreativeInventory::getInstance()->add($item);
    }

}
