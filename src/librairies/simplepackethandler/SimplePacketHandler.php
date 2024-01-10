<?php

declare(strict_types=1);

namespace tdm\librairies\simplepackethandler;

use InvalidArgumentException;
use tdm\librairies\simplepackethandler\interceptor\IPacketInterceptor;
use tdm\librairies\simplepackethandler\interceptor\PacketInterceptor;
use tdm\librairies\simplepackethandler\monitor\IPacketMonitor;
use tdm\librairies\simplepackethandler\monitor\PacketMonitor;
use pocketmine\event\EventPriority;
use pocketmine\plugin\Plugin;

final class SimplePacketHandler {

    /**
     * @param Plugin $registerer
     * @param int $priority
     * @param bool $handle_cancelled
     * @return IPacketInterceptor
     */
	public static function createInterceptor(Plugin $registerer, int $priority = EventPriority::NORMAL, bool $handle_cancelled = false): IPacketInterceptor {
		if ($priority === EventPriority::MONITOR) {
			throw new InvalidArgumentException("Cannot intercept packets at MONITOR priority");
		}
		return new PacketInterceptor($registerer, $priority, $handle_cancelled);
	}

    /**
     * @param Plugin $registerer
     * @param bool $handle_cancelled
     * @return IPacketMonitor
     */
	public static function createMonitor(Plugin $registerer, bool $handle_cancelled = false): IPacketMonitor {
		return new PacketMonitor($registerer, $handle_cancelled);
	}

}
