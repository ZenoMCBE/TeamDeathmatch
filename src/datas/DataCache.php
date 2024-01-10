<?php

namespace tdm\datas;

use pocketmine\utils\Config;

interface DataCache {

    /**
     * @return void
     */
    public function loadCache(): void;

    /**
     * @return array
     */
    public function getCache(): array;

    /**
     * @return void
     */
    public function unloadCache(): void;

    /**
     * @return Config
     */
    public function getProvider(): Config;

}
