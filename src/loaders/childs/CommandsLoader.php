<?php

namespace zenogames\loaders\childs;

use zenogames\commands\player\PingCommand;
use zenogames\commands\player\TpsCommand;
use zenogames\commands\staff\DiscordCommand;
use zenogames\commands\staff\ExactCoordsCommand;
use zenogames\commands\staff\match\MatchCommand;
use zenogames\commands\staff\SetRankCommand;
use zenogames\Zeno;
use zenogames\librairies\commando\BaseCommand;
use zenogames\loaders\Loader;
use pocketmine\command\Command;

final class CommandsLoader implements Loader {

    /**
     * @var array
     */
    private array $commandsToUnregister = [
        "ban-ip", "banlist", "clear", "defaultgamemode",
        "difficulty", "dumpmemory", "gc", "kill", "me",
        "pardon", "pardon-ip", "particle", "save",
        "save-on", "save-off", "say", "seed", "spawnpoint", "suicide",
        "tell", "title", "transferserver", "version"
    ];

    /**
     * @return void
     */
    public function onLoad(): void {
        $commands = [
            new DiscordCommand(),
            new ExactCoordsCommand(),
            new MatchCommand(),
            new PingCommand(),
            new SetRankCommand(),
            new TpsCommand()
        ];
        $commandMap = Zeno::getInstance()->getServer()->getCommandMap();
        foreach ($this->commandsToUnregister as $commandToUnregister) {
            $defaultCommand = $commandMap->getCommand($commandToUnregister);
            if ($defaultCommand instanceof Command) {
                $commandMap->unregister($defaultCommand);
            }
        }
        Zeno::getInstance()->getLogger()->notice("[Command] " . count($this->commandsToUnregister) . " commande(s) par défaut retirée(s) !");
        foreach ($commands as $command) {
            if (is_subclass_of($command, BaseCommand::class)) {
                foreach ($commandMap->getCommands() as $newCommand) {
                    if ($command->getName() === $newCommand->getName()) {
                        $commandMap->unregister($newCommand);
                    }
                }
                $commandMap->register($command->getName(), $command);
            }
        }
        Zeno::getInstance()->getLogger()->notice("[Command] " . count($commands) . " nouvelle(s) commande(s) ajoutée(s) !");
    }

    /**
     * @return void
     */
    public function onUnload(): void {}

}
