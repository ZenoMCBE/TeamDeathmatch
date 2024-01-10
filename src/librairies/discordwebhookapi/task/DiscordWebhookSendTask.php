<?php

declare(strict_types = 1);

namespace tdm\librairies\discordwebhookapi\task;

use pocketmine\scheduler\AsyncTask;

final class DiscordWebhookSendTask extends AsyncTask {

    /**
     * @param string $url
     * @param string $message
     */
    public function __construct(protected string $url, protected string $message) {}

    /**
     * @return void
     */
    public function onRun(): void {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->message);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        $this->setResult([curl_exec($ch), curl_getinfo($ch, CURLINFO_RESPONSE_CODE)]);
        curl_close($ch);
    }

    /**
     * @return void
     */
    public function onCompletion(): void {}

}
