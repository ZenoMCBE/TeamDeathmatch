<?php

namespace zenogames\managers;

use zenogames\utils\ids\MapIds;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;

final class MapManager {

    use SingletonTrait;

    /**
     * @var array
     */
    private array $mapPositions = [
        MapIds::CARGO => [
            1 => [76.5, 9, -12.5, 45, 0],
            2 => [52.5, 9, 63.5, 225, 0]
        ],
        MapIds::LEBRONZE => [
            1 => [154.5, 13, 184.5, 90, 0],
            2 => [80.5, 13, 170.5, 270, 0]
        ],
        MapIds::MARZIPAN => [
            1 => [106.5, 7, 54.5, 180, 0],
            2 => [106.5, 7, -45.5, 0, 0]
        ],
        MapIds::PADDINGTON => [
            1 => [10.5, 12, -36.5, 135, 0],
            2 => [-9.5, 12, 37.5, 315, 0]
        ],
        MapIds::REVOLUTION => [
            1 => [-111, 15, 70.5, 270, 0],
            2 => [-24, 15, 70.5, 90, 0]
        ],
        MapIds::ULTRAVIOLET => [
            1 => [133.5, 14, 16.5, 225, 0],
            2 => [183.5, 14, -33.5, 45, 0]
        ],
        MapIds::TOPAZ => [
            1 => [-85.5, 10, -33.5, 360, 0],
            2 => [-140.5, 10, 21.5, 270, 0]
        ]
    ];

    /**
     * @var array|array[]
     */
    private array $gappleGeneratorMapPosition = [
        MapIds::CARGO => [64.5, 19, 25.5],
        MapIds::LEBRONZE => [117.5, 12, 177.5],
        MapIds::MARZIPAN => [106.5, 11, 4.5],
        MapIds::PADDINGTON => [0.5, 6, 0.5],
        MapIds::REVOLUTION => [-67.5, 10, 70.5],
        MapIds::ULTRAVIOLET => [154.5, 12, -12.5],
        MapIds::TOPAZ => [-97.5, 8, 9.5]
    ];

    /**
     * @param Player $player
     * @return void
     */
    public function teleportToTeamSpawn(Player $player): void {
        $gameApi = GameManager::getInstance();
        $playerTeam = $gameApi->getPlayerTeam($player);
        $map = $gameApi->getMap();
        $position = $this->getMapSpawnPosition($map, $playerTeam);
        $yaw = $this->getMapPositionYaw($map, $playerTeam);
        $pitch = $this->getMapPositionPitch($map, $playerTeam);
        $player->teleport($position, $yaw, $pitch);
    }

    /**
     * @param string $map
     * @param int $team
     * @return Location
     */
    public function getMapSpawnPosition(string $map, int $team): Position {
        $mapPosition = $this->mapPositions[$map][$team];
        return new Position($mapPosition[0], $mapPosition[1], $mapPosition[2], Server::getInstance()->getWorldManager()->getWorldByName($map));
    }

    /**
     * @param string $map
     * @param int $team
     * @return float
     */
    public function getMapPositionYaw(string $map, int $team): float {
        $mapPosition = $this->mapPositions[$map][$team];
        return floatval($mapPosition[3]);
    }

    /**
     * @param string $map
     * @param int $team
     * @return float
     */
    public function getMapPositionPitch(string $map, int $team): float {
        $mapPosition = $this->mapPositions[$map][$team];
        return floatval($mapPosition[4]);
    }

    /**
     * @param string $map
     * @return Position
     */
    public function getGappleGeneratorMapPosition(string $map): Position {
        $gappleGeneratorMapPosition = $this->gappleGeneratorMapPosition[$map];
        return new Position($gappleGeneratorMapPosition[0], $gappleGeneratorMapPosition[1], $gappleGeneratorMapPosition[2], Server::getInstance()->getWorldManager()->getWorldByName($map));
    }

    /**
     * @param array $excludedMaps
     * @return string
     */
    public function getRandomMap(array $excludedMaps = []): string {
        $availableMaps = array_diff($this->getMaps(), $excludedMaps);
        return $availableMaps[array_rand($availableMaps)];
    }

    /**
     * @param string $map
     * @return int
     */
    public function getMapIdByMap(string $map): int {
        return match ($map) {
            MapIds::CARGO => 0,
            MapIds::LEBRONZE => 1,
            MapIds::MARZIPAN => 2,
            MapIds::PADDINGTON => 3,
            MapIds::REVOLUTION => 4,
            MapIds::ULTRAVIOLET => 5,
            MapIds::TOPAZ => 6
        };
    }

    /**
     * @return array
     */
    public function getMapCleanName(): array {
        return array_map('ucfirst', $this->getMaps());
    }

    /**
     * @return array
     */
    public function getMaps(): array {
        return MapIds::ALL_MAPS;
    }

}
