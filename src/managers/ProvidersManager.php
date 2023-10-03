<?php

namespace zenogames\managers;

use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use zenogames\TeamDeathmatch;

final class ProvidersManager {

    use SingletonTrait;

    /**
     * @var Config[]
     */
    private array $providers = [];

    /**
     * @return void
     */
    public function loadProviders(): void {
        $pluginDataFolder = TeamDeathmatch::getInstance()->getDataFolder();
        $this->addProvider("Rank", new Config($pluginDataFolder . "Rank.json", Config::JSON));
    }

    /**
     * @param string $name
     * @param Config $config
     * @return void
     */
    private function addProvider(string $name, Config $config): void {
        if (!$this->isAlreadyLoaded($name)) {
            $this->providers[$name] = $config;
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isAlreadyLoaded(string $name): bool {
        return in_array($name, $this->providers);
    }

    /**
     * @return int
     */
    public function getProviderCount(): int {
        return count($this->providers);
    }

    /**
     * @param string $name
     * @return Config|null
     */
    public function getProvider(string $name): ?Config {
        return $this->providers[$name] ?? null;
    }

}
