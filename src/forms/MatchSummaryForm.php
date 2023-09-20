<?php

namespace zenogames\forms;

use zenogames\librairies\formapi\Form;
use zenogames\librairies\formapi\ModalForm;
use zenogames\librairies\formapi\SimpleForm;
use zenogames\managers\GameManager;
use zenogames\managers\StatsManager;
use zenogames\utils\Constants;
use zenogames\utils\ids\StatsIds;
use zenogames\utils\Utils;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

final class MatchSummaryForm {

    use SingletonTrait;

    /**
     * @return SimpleForm
     */
    public function getMainForm(): SimpleForm {
        $form = new SimpleForm(function (Player $player, ?int $data = null) {
            if (is_int($data)) {
                $form = match ($data) {
                    0 => $this->getIndividualStatisticsListForm(),
                    1 => $this->getLeaderboardCategoryForm(),
                    default => null
                };
                if (!is_null($form)) {
                    $player->sendForm($form);
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aRésumé du match §l§q«");
        $form->setContent(Constants::PREFIX . "§fBienvenue dans le menu du §arésumé de la partie §f!");
        $form->addButton("§8Statistiques individuels");
        $form->addButton("§8Classements");
        return $form;
    }

    /**
     * @return SimpleForm
     */
    public function getIndividualStatisticsListForm(): SimpleForm {
        $gameApi = GameManager::getInstance();
        $statsApi = StatsManager::getInstance();
        $form = new SimpleForm(function (Player $player, ?string $data = null) use ($statsApi) {
            if (is_string($data)) {
                if ($statsApi->exist($data)) {
                    $player->sendForm($this->showIndividualPlayerStatisticsForm($data, $this->getIndividualStatisticsListForm()));
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aStatistiques individuels §l§q«");
        $form->setContent(Constants::PREFIX . "§fVeuillez cliquer sur un joueur pour consulter ses statistiques !");
        foreach ($statsApi->getTopScore() as $key => $value) {
            $playerTeam = $gameApi->playersTeam[$key];
            $playerTeamColor = $gameApi->getTeamColor($playerTeam);
            $playerMinecraftTeamColor = $gameApi->getMinecraftColorByColorId($playerTeamColor);
            $form->addButton($playerMinecraftTeamColor . $key, label: $key);
        }
        return $form;
    }

    /**
     * @return SimpleForm
     */
    public function getLeaderboardCategoryForm(): SimpleForm {
        $statsApi = StatsManager::getInstance();
        $form = new SimpleForm(function (Player $player, ?string $data = null) use ($statsApi) {
            if (is_string($data)) {
                if ($statsApi->isValidStats($data)) {
                    $player->sendForm($this->showSpecificLeaderboardForm($data));
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aClassements §l§q«");
        $form->setContent(Constants::PREFIX . "§fVeuillez cliquer sur une catégorie pour consulter le classement de celle-ci !");
        foreach ($statsApi->getAllStats() as $stats) {
            $form->addButton("§8" . $statsApi->getStatsNameByStats($stats), label: $stats);
        }
        return $form;
    }

    /**
     * @param string $stats
     * @return SimpleForm
     */
    public function showSpecificLeaderboardForm(string $stats): SimpleForm {
        $position = 1;
        $gameApi = GameManager::getInstance();
        $statsApi = StatsManager::getInstance();
        $form = new SimpleForm(function (Player $player, ?string $data = null) use ($statsApi, $stats) {
            if (is_string($data)) {
                if ($statsApi->exist($data)) {
                    $player->sendForm($this->showIndividualPlayerStatisticsForm($data, $this->showSpecificLeaderboardForm($stats)));
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aClassement " . $statsApi->getStatsNameByStats($stats) . " §l§q«");
        $form->setContent(Constants::PREFIX . "§fVoici le classement de la catégorie §a" . strtolower($statsApi->getStatsNameByStats($stats)) . " §f! Veuillez cliquer sur un joueur pour consulter ses statistiques !");
        foreach ($statsApi->getTopStats($stats) as $player => $stats) {
            if ($gameApi->hasPlayerTeam($player)) {
                $playerTeam = $gameApi->getPlayerTeam($player);
                $playerTeamColor = $gameApi->getTeamColor($playerTeam);
                $playerMinecraftTeamColor = $gameApi->getMinecraftColorByColorId($playerTeamColor);
            } else {
                $playerMinecraftTeamColor = "§0";
            }
            $form->addButton("§8[" . $position . "]\n" . $playerMinecraftTeamColor . Utils::getPlayerName($player, false) . " §7(§8" . $stats . "§7)", label: $player);
            $position++;
        }
        return $form;
    }

    /**
     * @param string $playerName
     * @param Form $returnForm
     * @return ModalForm
     */
    public function showIndividualPlayerStatisticsForm(string $playerName, Form $returnForm): ModalForm {
        $statsApi = StatsManager::getInstance();
        $form = new ModalForm(function (Player $player, ?bool $data = null) use ($returnForm) {
            if ($data) {
                $player->sendForm($returnForm);
            }
        });
        $form->setTitle("§l§q» §r§aStatistiques de " . Utils::getPlayerName($playerName, false) . " §l§q«§r");
        $form->setContent("Kill(s): " . $statsApi->get($playerName, StatsIds::KILL) . "\nAssistance(s): " . $statsApi->get($playerName, StatsIds::ASSIST) . "\nMort(s): " . $statsApi->get($playerName, StatsIds::DEATH) . "\nK/D: " . $statsApi->calculateKdr($playerName) . "\nK+A/D: " . $statsApi->calculateKadr($playerName) . "\nSérie de kill(s) actuel: " . $statsApi->get($playerName, StatsIds::KILLSTREAK) . "\nMeilleure série de kill(s): " . $statsApi->get($playerName, StatsIds::BEST_KILLSTREAK) . "\nFlèche(s) tirée(s): " . $statsApi->get($playerName, StatsIds::ARROW_SHOT) . "\nFlèche(s) touchée(s): " . $statsApi->get($playerName, StatsIds::ARROW_HIT) . "\nPourcentage de flèche(s) touchée(s): %" . $statsApi->calculateArrowHitByArrowShotPercentage($playerName) . "%%%%\nDégât(s) infligé(s): " . $statsApi->get($playerName, StatsIds::DAMAGE_DEALED) . "\nDégât(s) subit(s): " . $statsApi->get($playerName, StatsIds::DAMAGE_TAKEN) . "\nGapple(s) mangée(s): " . $statsApi->get($playerName, StatsIds::GOLDEN_APPLE_EATEN) . "\nCoup(s) critique(s): " . $statsApi->get($playerName, StatsIds::CRIT));
        $form->setButton1("§8Retour");
        $form->setButton2("§8Quitter");
        return $form;
    }

}
