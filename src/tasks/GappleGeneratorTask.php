<?php

namespace zenogames\tasks;

use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\particle\HeartParticle;
use pocketmine\world\sound\XpLevelUpSound;
use pocketmine\world\World;
use zenogames\managers\GameManager;
use zenogames\managers\MapManager;

final class GappleGeneratorTask extends Task {

    /**
     * @param string $map
     */
    public function __construct(protected string $map) {}

    /**
     * @return void
     */
    public function onRun(): void {
        $gameApi = GameManager::getInstance();
        if ($gameApi->isLaunched()) {
            $mapApi = MapManager::getInstance();
            $worldMap = Server::getInstance()->getWorldManager()->getWorldByName($this->map);
            if ($worldMap instanceof World) {
                $gappleGeneratorPosition = $mapApi->getGappleGeneratorMapPosition($this->map);
                $gappleGeneratorVector3 = $gappleGeneratorPosition->asVector3();
                $worldMap->dropItem($gappleGeneratorVector3, VanillaItems::GOLDEN_APPLE()->setCount(1));
                $angleIncrement = 360 / 8;
                for ($i = 0; $i < 8; $i++) {
                    $angle = $i * $angleIncrement * pi() / 180.0;
                    $center = $gappleGeneratorVector3->subtract(0, 0.8, 0);
                    $x = $center->x + cos($angle);
                    $y = $center->y;
                    $z = $center->z + sin($angle);
                    $particlePosition = new Vector3($x, $y, $z);
                    $worldMap->addParticle($particlePosition, new HeartParticle(2));
                }
                $worldMap->addSound($gappleGeneratorVector3, new XpLevelUpSound(5));
            }
        } else {
            $this->getHandler()?->cancel();
        }
    }

}
