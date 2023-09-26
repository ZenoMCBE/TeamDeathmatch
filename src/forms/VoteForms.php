<?php

namespace zenogames\forms;

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use zenogames\librairies\formapi\SimpleForm;
use zenogames\managers\VoteManager;
use zenogames\utils\Constants;

final class VoteForms {

    use SingletonTrait;

    /**
     * @return SimpleForm
     */
    public function getMainForm(): SimpleForm {
        $voteApi = VoteManager::getInstance();
        $form = new SimpleForm(function (Player $player, ?string $data = null) use ($voteApi) {
            if (is_string($data)) {
                if ($voteApi->isActive()) {
                    if (!$voteApi->hasVoted($player)) {
                        $voteApi->setVote($player, $data);
                        $player->sendMessage(Constants::PREFIX . "§fVous venez de voter pour la map §a" . ucfirst($data) . " §f!");
                    } else {
                        $player->sendMessage(Constants::PREFIX . "§cVotre vote n'a pas été comptabilisé car vous avez déjà voté pour une autre map.");
                    }
                } else {
                    $player->sendMessage(Constants::PREFIX . "§cVotre vote n'a pas été comptabilisé puisque la séance de votes est terminée.");
                }
            }
        });
        $form->setTitle("§r§l§q» §r§aVote de map §l§q«");
        $form->setContent(Constants::PREFIX . "§fBienvenue dans le §amenu de vote de map §f! Veuillez voter pour une map de votre choix afin de déterminer laquelle sera jouée durant cette partie !");
        foreach ($voteApi->getMaps() as $map) {
            $form->addButton("§8" . ucfirst($map) . "\n" . $voteApi->getMapVote($map) . " vote(s)", label: $map);
        }
        return $form;
    }

}
