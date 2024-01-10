<?php

/***
 * @noinspection PhpUnused
 *    ___                                          _
 *   / __\___  _ __ ___  _ __ ___   __ _ _ __   __| | ___
 *  / /  / _ \| '_ ` _ \| '_ ` _ \ / _` | '_ \ / _` |/ _ \
 * / /__| (_) | | | | | | | | | | | (_| | | | | (_| | (_) |
 * \____/\___/|_| |_| |_|_| |_| |_|\__,_|_| |_|\__,_|\___/
 *
 * Commando - A Command Framework virion for PocketMine-MP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Written by @CortexPE <https://CortexPE.xyz>
 *
 */

namespace tdm\librairies\commando;

use tdm\librairies\commando\constraint\BaseConstraint;
use tdm\librairies\commando\exception\InvalidErrorCode;
use tdm\librairies\commando\traits\ArgumentableTrait;
use tdm\librairies\commando\traits\IArgumentable;
use InvalidArgumentException;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

abstract class BaseCommand extends Command implements IArgumentable, IRunnable, PluginOwned
{
    use ArgumentableTrait;

    public const ERR_INVALID_ARG_VALUE = 0x01;
    public const ERR_TOO_MANY_ARGUMENTS = 0x02;
    public const ERR_NO_ARGUMENTS = 0x04;

    /** @var string[] */
    protected array $errorMessages = [
        self::ERR_INVALID_ARG_VALUE => TextFormat::RED . "La valeur '{value}' est invalide pour l'argument #{position}",
        self::ERR_TOO_MANY_ARGUMENTS => TextFormat::RED . "Trop d'arguments ont été fournis",
        self::ERR_NO_ARGUMENTS => TextFormat::RED . "Aucun argument n'est requis pour cette commande",
    ];

    protected CommandSender $currentSender;

    /** @var BaseSubCommand[] */
    private array $subCommands = [];

    /** @var BaseConstraint[] */
    private array $constraints = [];

    public function __construct(
        private PluginBase  $plugin,
        string              $name,
        Translatable|string $description = "",
        array               $aliases = []
    )
    {
        parent::__construct($name, $description, null, $aliases);

        $this->prepare();

        $this->usageMessage = $this->generateUsageMessage();
    }

    public function getOwningPlugin(): PluginBase
    {
        return $this->plugin;
    }

    final public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        $this->currentSender = $sender;

        if (!$this->testPermission($sender)) {
            return;
        }

        /** @var BaseCommand|BaseSubCommand $cmd */
        $cmd = $this;
        $passArgs = [];

        if (count($args) > 0) {
            if (isset($this->subCommands[($label = $args[0])])) {
                array_shift($args);
                $this->subCommands[$label]->execute($sender, $label, $args);
                return;
            }

            $passArgs = $this->attemptArgumentParsing($cmd, $args);
        }

        if ($passArgs !== null) {
            foreach ($cmd->getConstraints() as $constraint) {
                if (!$constraint->test($sender, $commandLabel, $passArgs)) {
                    $constraint->onFailure($sender, $commandLabel, $passArgs);
                    return;
                }
            }

            $cmd->onRun($sender, $commandLabel, $passArgs);
        }
    }

    /**
     * @param ArgumentableTrait $ctx
     * @param array $args
     *
     * @return array|null
     * @noinspection PhpMissingParamTypeInspection
     */
    private function attemptArgumentParsing($ctx, array $args): ?array
    {
        $dat = $ctx->parseArguments($args, $this->currentSender);

        if (!empty(($errors = $dat["errors"]))) {
            foreach ($errors as $error) {
                $this->sendError($error["code"], $error["data"]);
            }
            return null;
        }

        return $dat["arguments"];
    }

    public function sendError(int $errorCode, array $args = []): void
    {
        $str = $this->errorMessages[$errorCode];

        foreach ($args as $item => $value) {
            $str = str_replace(strval($item), (string)$value, $str);
        }

        $this->currentSender->sendMessage($str);
        $this->sendUsage();
    }

    protected function sendUsage(): void
    {
        $this->currentSender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
    }

    /**
     * @return BaseConstraint[]
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    abstract public function onRun(CommandSender $sender, string $aliasUsed, array $args): void;

    /**
     * @throws InvalidErrorCode
     */
    public function setErrorFormats(array $errorFormats): void
    {
        foreach ($errorFormats as $errorCode => $format) {
            $this->setErrorFormat($errorCode, $format);
        }
    }

    /**
     * @throws InvalidErrorCode
     */
    public function setErrorFormat(int $errorCode, string $format): void
    {
        if (!isset($this->errorMessages[$errorCode])) {
            throw new InvalidErrorCode("Invalid error code 0x" . dechex($errorCode));
        }
        $this->errorMessages[$errorCode] = $format;
    }

    public function registerSubCommand(BaseSubCommand $subCommand): void
    {
        $keys = $subCommand->getAliases();

        array_unshift($keys, $subCommand->getName());
        $keys = array_unique($keys);

        foreach ($keys as $key) {
            if (!isset($this->subCommands[$key])) {
                $subCommand->setParent($this);
                $this->subCommands[$key] = $subCommand;
            } else {
                throw new InvalidArgumentException("SubCommand with same name / alias for '$key' already exists");
            }
        }
    }

    /**
     * @return BaseSubCommand[]
     */
    public function getSubCommands(): array
    {
        return $this->subCommands;
    }

    public function addConstraint(BaseConstraint $constraint): void
    {
        $this->constraints[] = $constraint;
    }

    public function getUsageMessage(): string
    {
        return $this->getUsage();
    }

    public function setCurrentSender(CommandSender $sender): void
    {
        $this->currentSender = $sender;
    }
}
