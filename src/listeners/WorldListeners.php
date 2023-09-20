<?php /* @noinspection ALL */

namespace zenogames\listeners;

use pocketmine\data\bedrock\BiomeIds;
use pocketmine\event\Listener;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\world\format\SubChunk;
use pocketmine\world\format\PalettedBlockArray;

final class WorldListeners implements Listener {

    /**
     * @param ChunkLoadEvent $event
     * @return void
     */
    public function onChunkLoad(ChunkLoadEvent $event): void {
        $chunk = $event->getChunk();
        foreach ($chunk->getSubChunks() as $y => $subChunk) {
            if ($subChunk->getBiomeArray()->getPalette() !== [BiomeIds::JUNGLE]) {
                $chunk->setSubChunk($y, new SubChunk(
                    $subChunk->getEmptyBlockId(),
                    $subChunk->getBlockLayers(),
                    new PalettedBlockArray(BiomeIds::JUNGLE),
                    $subChunk->getBlockSkyLightArray(),
                    $subChunk->getBlockLightArray()
                ));
            }
        }
    }

}
