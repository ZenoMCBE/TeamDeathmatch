<?php

declare(strict_types = 1);

namespace tdm\librairies\discordwebhookapi;

use tdm\librairies\discordwebhookapi\task\DiscordWebhookSendTask;
use pocketmine\Server;

final class Webhook {

    /**
     * @var string
     */
    protected string $url;

    /**
     * @param string $url
     */
    public function __construct(string $url) {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getURL(): string {
        return $this->url;
    }

    /**
     * @return bool
     */
    public function isValid(): bool {
        return filter_var($this->url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @param Message $message
     * @return void
     */
    public function send(Message $message): void {
        Server::getInstance()->getAsyncPool()->submitTask(new DiscordWebhookSendTask($this->getURL(), json_encode($message)));
    }

}
