<?php


declare(strict_types = 1);

namespace zenogames\librairies\discordwebhookapi;

use JsonSerializable;

final class Message implements JsonSerializable {

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @param string $content
     * @return void
     */
    public function setContent(string $content): void {
        $this->data["content"] = $content;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string {
        return $this->data["content"];
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string {
        return $this->data["username"];
    }

    /**
     * @param string $username
     * @return void
     */
    public function setUsername(string $username): void {
        $this->data["username"] = $username;
    }

    /**
     * @return string|null
     */
    public function getAvatarURL(): ?string {
        return $this->data["avatar_url"];
    }

    /**
     * @param string $avatarURL
     * @return void
     */
    public function setAvatarURL(string $avatarURL): void {
        $this->data["avatar_url"] = $avatarURL;
    }

    /**
     * @param Embed $embed
     * @return void
     */
    public function addEmbed(Embed $embed): void {
        if (!empty(($arr = $embed->asArray()))) {
            $this->data["embeds"][] = $arr;
        }
    }

    /**
     * @param bool $ttsEnabled
     * @return void
     */
    public function setTextToSpeech(bool $ttsEnabled): void {
        $this->data["tts"] = $ttsEnabled;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array {
        return $this->data;
    }
}
