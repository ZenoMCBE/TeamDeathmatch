<?php

namespace tdm\forms;

use tdm\librairies\formapi\{CustomForm, SimpleForm};
use tdm\managers\{GameManager, MapManager, ScoreboardManager, VoteManager};
use tdm\utils\Constants;
use tdm\utils\ids\{ColorIds, MapIds};
use tdm\utils\Utils;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

final class GameManagementForm {

    use SingletonTrait;

    /**
     * @return SimpleForm
     */
    public function getMainForm(): SimpleForm {
        $form = new SimpleForm(function (Player $player, ?int $data = null) {
            if (is_int($data)) {
                $formToShow = match ($data) {
                    0 => $this->getGoalManagementForm(),
                    1 => $this->getTimeManagementForm(),
                    2 => $this->getTeamManagementForm(),
                    3 => $this->getMapManagementForm(),
                    4 => $this->getConfirmationLaunchForm(),
                    default => null
                };
                if (!is_null($formToShow)) {
                    $player->sendForm($formToShow);
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aParamètres §l§q«");
        $form->setContent(Constants::PREFIX . "§fBienvenue dans le menu des §aparamètres §fde la partie !");
        $form->addButton("§8Gérer l'objectif");
        $form->addButton("§8Gérer le chrono");
        $form->addButton("§8Gérer les équipes");
        $form->addButton("§8Gérer la map");
        $form->addButton("§8Lancer la partie");
        return $form;
    }

    /**
     * @return CustomForm
     */
    public function getGoalManagementForm(): CustomForm {
        $gameApi = GameManager::getInstance();
        $form = new CustomForm(function (Player $player, ?array $data = null) use ($gameApi) {
            if (is_array($data)) {
                if (is_numeric($data[0])) {
                    if ($data[0] >= 1 && $data[0] <= 50) {
                        if (intval($data[0]) !== $gameApi->getGoal()) {
                            $goal = intval($data[0]);
                            $gameApi->setGoal($goal);
                            Server::getInstance()->broadcastMessage(Constants::PREFIX . "§fL'objectif a été défini à §a" . $goal . " kill(s) §f!");
                            foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                                Utils::playSound($onlinePlayer, "mob.enderdragon.hit");
                            }
                        }
                    }
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aGestion de l'objectif §l§q«");
        $form->addSlider("Objectif", 1, 50, default: $gameApi->getGoal());
        return $form;
    }

    /**
     * @return CustomForm
     */
    public function getTimeManagementForm(): CustomForm {
        $gameApi = GameManager::getInstance();
        $form = new CustomForm(function (Player $player, ?array $data = null) use ($gameApi) {
            if (is_array($data)) {
                if (is_numeric($data[0])) {
                    if ($data[0] >= 1 && $data[0] <= 20) {
                        $chrono = intval($data[0]);
                        if (($chrono * 60) !== $gameApi->getRemainingTime()) {
                            $gameApi->setRemainingTime($chrono * 60);
                            Server::getInstance()->broadcastMessage(Constants::PREFIX . "§fLe chronomètre a été défini à §a" . $chrono . " minute(s) §f!");
                            foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                                Utils::playSound($onlinePlayer, "mob.enderdragon.hit");
                            }
                        }
                    }
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aGestion du chrono §l§q«");
        $form->addSlider("Chrono", 1, 20, default: intval($gameApi->getRemainingTime() / 60));
        return $form;
    }

    /**
     * @return SimpleForm
     */
    public function getTeamManagementForm(): SimpleForm {
        $gameApi = GameManager::getInstance();
        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($gameApi) {
            if (is_int($data)) {
                switch ($data) {
                    case 0:
                        $player->sendForm($this->selectTeamColorForm());
                        break;
                    case 1:
                        $player->sendForm($this->selectPlayersTeamLimitForm());
                        break;
                    case 2:
                        $gameApi->resetTeamPlayers();
                        Server::getInstance()->broadcastMessage(Constants::PREFIX . "§fLes équipes ont été automatiquement réinitialisées !");
                        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                            ScoreboardManager::getInstance()->updateTeam($onlinePlayer);
                            Utils::playSound($onlinePlayer, "mob.enderdragon.hit");
                        }
                        break;
                    case 3:
                        GameManager::getInstance()->randomizeTeam();
                        break;
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aGestion des équipes §l§q«");
        $form->setContent(Constants::PREFIX . "§fBienvenue dans le §amenu de gestion des équipes §f!");
        $form->addButton("§8Modifier les couleurs");
        $form->addButton("§8Définir la limite de joueurs");
        $form->addButton("§8Réinitialiser les équipes");
        $form->addButton("§8Randomizer les équipes");
        return $form;
    }

    /**
     * @return CustomForm
     */
    public function selectTeamColorForm(): CustomForm {
        $gameApi = GameManager::getInstance();
        $form = new CustomForm(function (Player $player, ?array $data = null) use ($gameApi) {
            if (is_array($data)) {
                if ($data[0] !== $data[1]) {
                    $newColorTeam = function (int $data): string {
                        return match ($data) {
                            0 => ColorIds::RED,
                            1 => ColorIds::ORANGE,
                            2 => ColorIds::YELLOW,
                            3 => ColorIds::LIME,
                            4 => ColorIds::AQUA,
                            5 => ColorIds::BLUE,
                            6 => ColorIds::PURPLE,
                            7 => ColorIds::PINK,
                            8 => ColorIds::WHITE,
                            9 => ColorIds::BLACK
                        };
                    };
                    $firstTeamNewColor = $newColorTeam(intval($data[0]));
                    $secondTeamNewColor = $newColorTeam(intval($data[1]));
                    $gameApi->setTeamColor(1, $firstTeamNewColor);
                    $gameApi->setTeamColor(2, $secondTeamNewColor);
                    $firstTeamFormattedColorName = $gameApi->getFormattedColorNameByColorId(1);
                    $secondTeamFormattedColorName = $gameApi->getFormattedColorNameByColorId(2);
                    Server::getInstance()->broadcastMessage(Constants::PREFIX . "§fLa couleur des équipes ont été modifiées pour " . $firstTeamFormattedColorName . " §fet " . $secondTeamFormattedColorName . " §f!");
                    foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                        ScoreboardManager::getInstance()->updateTeam($onlinePlayer);
                        Utils::playSound($onlinePlayer, "mob.enderdragon.hit");
                    }
                } else {
                    $player->sendMessage(Constants::PREFIX . "§cLes deux couleurs des équipes doivent être obligatoirement différentes.");
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aCouleur des équipes §l§q«");
        $form->addDropdown("Couleur Équipe #1", $gameApi->getColoredColors(), $gameApi->getColorId($gameApi->getTeamColor(1)));
        $form->addDropdown("Couleur Équipe #2", $gameApi->getColoredColors(), $gameApi->getColorId($gameApi->getTeamColor(2)));
        return $form;
    }

    /**
     * @return CustomForm
     */
    public function selectPlayersTeamLimitForm(): CustomForm {
        $gameApi = GameManager::getInstance();
        $form = new CustomForm(function (Player $player, ?array $data = null) use ($gameApi) {
            if (is_array($data)) {
                if (is_numeric($data[0])) {
                    if ($data[0] >= 1 && $data[0] <= 5) {
                        if (intval($data[0]) !== $gameApi->getTeamPlayersLimit()) {
                            $teamLimit = intval($data[0]);
                            $gameApi->setTeamPlayersLimit($teamLimit);
                            Server::getInstance()->broadcastMessage(Constants::PREFIX . "§fLa limite de joueurs par équipe a été définie à §a" . $teamLimit . " joueur(s) §f!");
                            foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                                Utils::playSound($onlinePlayer, "mob.enderdragon.hit");
                            }
                        }
                    }
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aLimite de joueurs §l§q«");
        $form->addSlider("Limite de joueurs", 1, 5, default: $gameApi->getTeamPlayersLimit());
        return $form;
    }

    /**
     * @return SimpleForm
     */
    public function getMapManagementForm(): SimpleForm {
        $mapApi = MapManager::getInstance();
        $gameApi = GameManager::getInstance();
        $voteApi = VoteManager::getInstance();
        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($gameApi, $mapApi, $voteApi) {
            if (is_int($data)) {
                switch ($data) {
                    case 0:
                        $player->sendForm($this->selectMapForm());
                        break;
                    case 1:
                        $gameApi->setMap($mapApi->getRandomMap());
                        $newMap = ucfirst($gameApi->getMap());
                        Server::getInstance()->broadcastMessage(Constants::PREFIX . "§fLa map a été sélectionnée aléatoirement pour §a" . $newMap . " §f!");
                        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                            Utils::playSound($onlinePlayer, "mob.enderdragon.hit");
                        }
                        break;
                    case 2:
                        $voteApi->start();
                        break;
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aGestion de la map §l§q«");
        $form->setContent(Constants::PREFIX . "§fBienvenue dans le §amenu de gestion de la map §f!");
        $form->addButton("§8Sélectionner la map");
        $form->addButton("§8Randomizer la map");
        $form->addButton("§8Démarrer un vote de map");
        return $form;
    }

    /**
     * @return CustomForm
     */
    public function selectMapForm(): CustomForm {
        $gameApi = GameManager::getInstance();
        $mapApi = MapManager::getInstance();
        $form = new CustomForm(function (Player $player, ?array $data = null) use ($gameApi) {
            if (is_array($data)) {
                $newMap = match (intval($data[0])) {
                    0 => MapIds::CARGO,
                    1 => MapIds::LEBRONZE,
                    2 => MapIds::MARZIPAN,
                    3 => MapIds::PADDINGTON,
                    4 => MapIds::REVOLUTION,
                    5 => MapIds::ULTRAVIOLET,
                    6 => MapIds::TOPAZ,
                    default => null
                };
                if (
                    !is_null($newMap) &&
                    $gameApi->getMap() !== $newMap
                ) {
                    $gameApi->setMap($newMap);
                    Server::getInstance()->broadcastMessage(Constants::PREFIX . "§fLa map a été modifié pour §a" . ucfirst($newMap) . " §f!");
                    foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
                        Utils::playSound($onlinePlayer, "mob.enderdragon.hit");
                    }
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aGestion de la map §l§q«");
        $form->addDropdown("Map", $mapApi->getMapCleanName(), $mapApi->getMapIdByMap($gameApi->getMap()));
        return $form;
    }

    /**
     * @return SimpleForm
     */
    public function getConfirmationLaunchForm(): SimpleForm {
        $gameApi = GameManager::getInstance();
        $firstTeamFormattedColorName = $gameApi->getFormattedColorNameByColorId(1);
        $secondTeamFormattedColorName = $gameApi->getFormattedColorNameByColorId(2);
        $form = new SimpleForm(function (Player $player, ?int $data = null) use ($gameApi) {
            if ($data === 0) {
                $gameApi->start();
            }
        });
        $form->setTitle("§r§l§q» §r§aConfirmation §l§q«");
        $form->setContent(Constants::PREFIX . "§fSouhaitez-vous réellement lancer la partie ? Voici les paramètres de celle-ci :\n\n§fChrono§7: §a " . intval($gameApi->getRemainingTime() / 60) . " minute(s)\n§fObjectif§7: §a" . $gameApi->getGoal() . "\n§fLimite de joueurs par équipe§7: §a" . $gameApi->getTeamPlayersLimit() . "\n§fCouleur Équipe #1§7: " . $firstTeamFormattedColorName . "\n§fCouleur Équipe #2§7: " . $secondTeamFormattedColorName . "\n§fMap§7: §a" . ucfirst($gameApi->getMap()));
        $form->addButton("§8Confirmer");
        $form->addButton("§cAnnuler");
        return $form;
    }

}
