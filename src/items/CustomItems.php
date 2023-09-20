<?php

namespace zenogames\items;

use pocketmine\item\Item;
use pocketmine\utils\CloningRegistryTrait;
use zenogames\items\childs\CustomBow;

final class CustomItems {

    use CloningRegistryTrait;

    /**
     * @var array
     */
    private static array $aliases = [];

    /**
     * @param int $typeId
     * @return string|null
     */
    public static function getAliases(int $typeId): ?string {
        return self::$aliases[$typeId] ?? null;
    }

    /**
     * @return Item[]
     * @phpstan-return array<string, Item>
     * @noinspection PhpUndefinedClassInspection
     */
    public static function getAll(): array {
        /* @var Item[] $result */
        $result = self::_registryGetAll();
        return $result;
    }

    /**
     * @return void
     */
    protected static function setup(): void {
        self::register("bow", new CustomBow());
    }

    /**
     * @param string $name
     * @param Item $item
     * @return void
     */
    protected static function register(string $name, Item $item): void {
        self::$aliases[$item->getTypeId()] = $name;
        self::_registryRegister($name, $item);
    }

}
