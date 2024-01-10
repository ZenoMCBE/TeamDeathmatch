<?php

namespace tdm\loaders\childs;

use tdm\commands\player\{PingCommand, TpsCommand};
use tdm\commands\staff\ExactCoordsCommand;
use tdm\commands\staff\match\MatchCommand;
use tdm\commands\staff\SetRankCommand;
use tdm\librairies\commando\BaseCommand;
use tdm\loaders\Loader;
use pocketmine\command\Command;
use tdm\TeamDeathmatch;

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
        $plugin = TeamDeathmatch::getInstance();
        $commands = [
            // new DiscordCommand(),
            new ExactCoordsCommand(),
            new MatchCommand(),
            new PingCommand(),
            new SetRankCommand(),
            new TpsCommand()
        ];
        $commandMap = TeamDeathmatch::getInstance()->getServer()->getCommandMap();
        foreach ($this->commandsToUnregister as $commandToUnregister) {
            $defaultCommand = $commandMap->getCommand($commandToUnregister);
            if ($defaultCommand instanceof Command) {
                $commandMap->unregister($defaultCommand);
            }
        }
        $plugin->getLogger()->notice("[Command] " . count($this->commandsToUnregister) . " commande(s) par défaut retirée(s) !");
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
        $plugin->getLogger()->notice("[Command] " . count($commands) . " nouvelle(s) commande(s) ajoutée(s) !");
    }

    /**
     * @return void
     */
    public function onUnload(): void {}

}
