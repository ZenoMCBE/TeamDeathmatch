<?php

declare(strict_types = 1);

namespace zenogames\librairies\formapi;

use pocketmine\form\Form as IForm;
use pocketmine\player\Player;
use ReturnTypeWillChange;

abstract class Form implements IForm{

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var callable|null
     */
    private $callable;

    /**
     * @param callable|null $callable
     */
    public function __construct(?callable $callable) {
        $this->callable = $callable;
    }

    /**
     * @return callable|null
     */
    public function getCallable(): ?callable {
        return $this->callable;
    }

    /**
     * @param callable|null $callable
     * @return void
     */
    public function setCallable(?callable $callable): void {
        $this->callable = $callable;
    }

    /**
     * @param Player $player
     * @param $data
     * @return void
     */
    public function handleResponse(Player $player, $data): void {
        $this->processData($data);
        $callable = $this->getCallable();
        if($callable !== null) {
            $callable($player, $data);
        }
    }

    /**
     * @param $data
     * @return void
     */
    public function processData(&$data): void {}

    /**
     * @return array
     */
    #[ReturnTypeWillChange] public function jsonSerialize(): array {
        return $this->data;
    }

}
