<?php

namespace zenogames\managers;

use pocketmine\world\World;
use zenogames\tasks\GappleGeneratorTask;
use zenogames\tasks\RestartTask;
use zenogames\Zeno;
use zenogames\librairies\invmenu\InvMenu;
use zenogames\librairies\invmenu\transaction\DeterministicInvMenuTransaction;
use zenogames\librairies\invmenu\type\InvMenuTypeIds;
use zenogames\tasks\AssistTask;
use zenogames\tasks\DeathTask;
use zenogames\tasks\GameTask;
use zenogames\utils\Constants;
use zenogames\utils\ids\ColorIds;
use zenogames\utils\ids\KitIds;
use zenogames\utils\ids\ScoreboardTypeIds;
use zenogames\utils\ids\StatsIds;
use zenogames\utils\Utils;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\animation\CriticalHitAnimation;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\EntityAttackSound;

final class GameManager {

    use SingletonTrait;

    public const WAITING_STATUS = 0;
    public const LAUNCH_STATUS = 1;
    public const END_STATUS = 2;

    /**
     * @var string|null
     */
    private ?string $gameId = null;

    /**
     * @var array
     */
    private array $teams = [
        1 => [
            "color" => null,
            "points" => 0,
            "players" => []
        ],
        2 => [
            "color" => null,
            "points" => 0,
            "players" => []
        ]
    ];

    /**
     * @var array
     */
    public array $playersTeam = [];

    /**
     * @var string
     */
    private string $map;

    /**
     * @var int
     */
    private int $status = self::WAITING_STATUS;

    /**
     * @var int
     */
    private int $goal = 50;

    /**
     * @var int
     */
    private int $time = 600;

    /**
     * @var int
     */
    private int $teamLimit = 5;

    /**
     * @var int|null
     */
    private ?int $winnerTeam = null;

    /**
     * @return void
     */
    public function loadDefaultParameters(): void {
        $firstTeamColor = $this->getColors()[array_rand($this->getColors())];
        $secondTeamColor = $this->getColors()[array_rand($this->getColors())];
        while ($firstTeamColor == $secondTeamColor) {
            $secondTeamColor = $this->getColors()[array_rand($this->getColors())];
        }
        $this->setTeamColor(1, $firstTeamColor);
        $this->setTeamColor(2, $secondTeamColor);
        $this->setStatus(self::WAITING_STATUS);
        $this->setGoal(50);
        $this->setRemainingTime(600);
        $this->setTeamPlayersLimit(5);
        $this->setWinnerTeam(null);
        $this->setMap(MapManager::getInstance()->getRandomMap());
        $this->setGameId($this->generateGameId());
    }

    /**
     * @return void
     * @noinspection PhpDeprecationInspection
     */
    public function start(): void {
        $webApi = WebApiManager::getInstance();
        $scheduler = Zeno::getInstance()->getScheduler();
        [$firstTeamPlayers, $secondTeamPlayers] = [$this->getTeamPlayers(1), $this->getTeamPlayers(2)];
        $gamePlayers = array_merge($firstTeamPlayers, $secondTeamPlayers);
        foreach ($gamePlayers as $gamePlayer) {
            $player = Server::getInstance()->getPlayerByPrefix(Utils::getPlayerName($gamePlayer, false));
            if ($player instanceof Player) {
                MapManager::getInstance()->teleportToTeamSpawn($player);
                KitManager::getInstance()->send($player, KitIds::GAME);
                Utils::playSound($player, "mob.enderdragon.growl");
                $player->sendMessage("§r§l§q» §r§aInformations de la partie §l§q«");
                $player->sendMessage("§l§q| §r§fChrono§7: §a" . intval($this->getRemainingTime() / 60) . " minute(s)");
                $player->sendMessage("§l§q| §r§fMap§7: §a" . ucfirst($this->getMap()));
                $scheduler->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
                    $player->sendTitle("§r§l§q» §r§aTeam Deathmatch §l§q«");
                    $player->sendSubTitle("§7Votre objectif est de tuer " . $this->getGoal() . " joueur(s) avec votre équipe !");
                }), 3);
            }
        }
        $teamsData = [
            ["teamOne" => $firstTeamPlayers],
            ["teamTwo" => $secondTeamPlayers],
        ];
        foreach ($teamsData as $teamData) {
            foreach ($teamData as $teamName => $players) {
                $formattedPlayers = array_map(fn ($player) => Utils::getPlayerName($player, false), $players);
                $webApi->sendTeamToServer($formattedPlayers, $teamName);
            }
        }
        $this->setStatus(self::LAUNCH_STATUS);
        $scheduler->scheduleRepeatingTask(new GameTask(), 20);
        $scheduler->scheduleRepeatingTask(new AssistTask(), 20);
        $scheduler->scheduleDelayedRepeatingTask(new GappleGeneratorTask($this->getMap()), 20*30, 20*30);
    }

    /**
     * @param int|null $team
     * @return void
     * @noinspection PhpDeprecationInspection
     */
    public function end(?int $team): void {
        $permanentStatsApi = Zeno::getInstance()->getStatsApi();
        $statsApi = StatsManager::getInstance();
        $discordWebhookApi = DiscordWebhookManager::getInstance();
        $statsApi->generatePlayersScore();
        $gamePlayers = array_merge($this->getTeamPlayers(1), $this->getTeamPlayers(2));
        $this->setWinnerTeam($team);
        $this->setStatus(self::END_STATUS);
        $mapWorld = Server::getInstance()->getWorldManager()->getWorldByName($this->getMap());
        if ($mapWorld instanceof World) {
            foreach ($mapWorld->getEntities() as $entity) {
                if (
                    !$entity instanceof Player &&
                    !$entity->isFlaggedForDespawn()
                ) {
                    $entity->close();
                }
            }
        }
        if (!is_null($team)) {
            $teamColor = $this->getTeamColor($team);
            $teamColorName = $this->getColorNameByColorId($teamColor);
            $minecraftTeamColor = $this->getMinecraftColorByColorId($teamColor);
            $title = "§r§l§8» §r" . $minecraftTeamColor . $teamColorName . " §l§8«";
            $subTitle = "§7Victoire de l'équipe " . $teamColorName . " !";
        } else {
            $title = "§r§l§8» §r§7Égalité §l§8«";
            $subTitle = "§7Aucune équipe ne ressort vainqueur de cette partie !";
        }
        $firstAverageTeamScore = $statsApi->calculateAverageTeamScore(1);
        $secondAverageTeamScore = $statsApi->calculateAverageTeamScore(2);
        $firstTeamAverageTeamLeague = $this->getAverageTeamLeague(1);
        $secondTeamAverageTeamLeague = $this->getAverageTeamLeague(2);
        foreach ($gamePlayers as $gamePlayer) {
            $player = Server::getInstance()->getPlayerByPrefix(Utils::getPlayerName($gamePlayer, false));
            if ($player instanceof Player) {
                $player->sendTitle($title);
                $player->sendSubTitle($subTitle);
                Utils::teleportToEndedMap($player);
                KitManager::getInstance()->send($player, KitIds::END);
                ScoreboardManager::getInstance()->sendScoreboard($player, ScoreboardTypeIds::ENDED);
                $statsApi->showScoreMessage($player);
                $playerTeam = $this->getPlayerTeam($player);
                $result = (!is_null($team)) ? ($this->getWinnerTeam() === $playerTeam) : null;
                $permanentStatsApi->getStatsManager()->update($player, $statsApi->getPlayerScore($player), $statsApi->getAll($player), $result);
                $averageTeamScore = $playerTeam === 1 ? $firstAverageTeamScore : $secondAverageTeamScore;
                $averageTeamLeague = $playerTeam === 1 ? $firstTeamAverageTeamLeague : $secondTeamAverageTeamLeague;
                $permanentStatsApi->getEloManager()->update($player, $statsApi->getPlayerScore($player), $averageTeamScore, $averageTeamLeague, $result);
                Utils::playSound($player, "mob.enderdragon.growl");
            }
        }
        $individualResultElos = [];
        $firstTeamColorName = $this->getColorNameByColorId($this->getTeamColor(1));
        $secondTeamColorName = $this->getColorNameByColorId($this->getTeamColor(2));
        foreach ($permanentStatsApi->getEloManager()->getResultElo() as $player => $resultElo) {
            $playerTeam = $this->getPlayerTeam($player) - 1;
            $individualResultElos[$playerTeam][$player] = $permanentStatsApi->getEloManager()->getStringResultElo($player);
        }
        foreach ($individualResultElos as &$individualResultElo) {
            ksort($individualResultElo);
        }
        $discordWebhookApi->sendMatchSummary($this->getGameId(), $this->getMap(), $this->getWinnerTeam(), [$firstTeamColorName, $secondTeamColorName], $individualResultElos);
        $firstTeamIndividualPlayersStats = $statsApi->getIndividualPlayersTeamStats(1);
        $secondTeamIndividualPlayersStats = $statsApi->getIndividualPlayersTeamStats(2);
        $discordWebhookApi->sendStatsSummary($this->getGameId(), $this->getMap(), $this->getWinnerTeam(), [$firstTeamColorName, $secondTeamColorName], [$firstTeamIndividualPlayersStats, $secondTeamIndividualPlayersStats]);
        $this->setGameId(null);
        Zeno::getInstance()->getScheduler()->scheduleRepeatingTask(new RestartTask(), 20);
    }

    /**
     * @return void
     * @noinspection PhpDeprecationInspection
     */
    public function restart(): void {
        $this->resetTeams();
        $firstTeamColor = $this->getColors()[array_rand($this->getColors())];
        $secondTeamColor = $this->getColors()[array_rand($this->getColors())];
        while ($firstTeamColor == $secondTeamColor) {
            $secondTeamColor = $this->getColors()[array_rand($this->getColors())];
        }
        $this->setTeamColor(1, $firstTeamColor);
        $this->setTeamColor(2, $secondTeamColor);
        $this->setStatus(self::WAITING_STATUS);
        $this->setGoal(50);
        $this->setRemainingTime(600);
        $this->setTeamPlayersLimit(5);
        $this->setWinnerTeam(null);
        $this->setMap(MapManager::getInstance()->getRandomMap());
        $this->setGameId($this->generateGameId());
        $assistApi = AssistManager::getInstance();
        $scoreboardApi = ScoreboardManager::getInstance();
        $statsApi = StatsManager::getInstance();
        $permanentStatsApi = Zeno::getInstance()->getStatsApi();
        $assistApi->reset();
        $statsApi->reset();
        $permanentStatsApi->getEloManager()->resetResultElo();
        $this->playersTeam = [];
        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $this->setPlayerTeam($onlinePlayer, 0);
            $assistApi->create($onlinePlayer);
            $statsApi->create($onlinePlayer);
            $scoreboardApi->sendScoreboard($onlinePlayer, ScoreboardTypeIds::WAITING);
            $scoreboardApi->updateOnlinePlayers(false);
            $this->updateNametag($onlinePlayer);
            Utils::teleportToWaitingMap($onlinePlayer);
            KitManager::getInstance()->send($onlinePlayer, KitIds::WAITING);
            Utils::playSound($onlinePlayer, "portal.travel");
        }
        WebApiManager::getInstance()->clearGame();
    }

    /**
     * @param Player $entity
     * @param Player $damager
     * @param EntityDamageByEntityEvent|EntityDamageByChildEntityEvent $event
     * @return void
     * @noinspection PhpDeprecationInspection
     */
    public function onDeath(Player $entity, Player $damager, EntityDamageByEntityEvent|EntityDamageByChildEntityEvent $event): void {
        $finalDamage = $event->getFinalDamage();
        $assistApi = AssistManager::getInstance();
        $statsApi = StatsManager::getInstance();
        $statsApi->add($entity, StatsIds::DEATH);
        $statsApi->set($entity, StatsIds::KILLSTREAK, 0);
        $statsApi->add($entity, StatsIds::DAMAGE_TAKEN, intval($finalDamage));
        $statsApi->showStatsPopup($entity);
        $statsApi->add($damager, StatsIds::KILL);
        $statsApi->add($damager, StatsIds::KILLSTREAK);
        $statsApi->add($damager, StatsIds::DAMAGE_DEALED, intval($finalDamage));
        if ($statsApi->get($damager, StatsIds::BEST_KILLSTREAK) < $statsApi->get($damager, StatsIds::KILLSTREAK)) {
            $statsApi->set($damager, StatsIds::BEST_KILLSTREAK, $statsApi->get($damager, StatsIds::KILLSTREAK));
        }
        $statsApi->showStatsPopup($damager);
        $entity->setHealth($entity->getMaxHealth());
        $damager->broadcastSound(new EntityAttackSound());
        if ($event->getModifier(EntityDamageEvent::MODIFIER_CRITICAL) > 0) {
            $statsApi->add($damager, StatsIds::CRIT);
            $damager->broadcastAnimation(new CriticalHitAnimation($entity));
        }
        Utils::playSound($damager, "random.orb");
        $entityTeam = $this->getPlayerTeam($entity);
        $damagerTeam = $this->getPlayerTeam($damager);
        $entityTeamColor = $this->getTeamColor($entityTeam);
        $damagerTeamColor = $this->getTeamColor($damagerTeam);
        $entityMinecraftTeamColor = $this->getMinecraftColorByColorId($entityTeamColor);
        $damagerMinecraftTeamColor = $this->getMinecraftColorByColorId($damagerTeamColor);
        $assisters = $assistApi->getAssistersWithoutKiller($entity, $damager);
        $formattedAssisters = !empty($assisters)
            ? " §8[" . $damagerMinecraftTeamColor . implode(", ", $assisters) . "§8]"
            : "";
        if ($event instanceof EntityDamageByChildEntityEvent) {
            $shootDistance = round($damager->getPosition()->distance($entity->getPosition()), 2);
            Server::getInstance()->broadcastMessage(Constants::PREFIX . $entityMinecraftTeamColor . $entity->getName() . " §7a été tiré dessus par " . $damagerMinecraftTeamColor . $damager->getName() . " §7! §8(§7" . $shootDistance . " block(s)§8)" . $formattedAssisters);
        } else {
            Server::getInstance()->broadcastMessage(Constants::PREFIX . $entityMinecraftTeamColor . $entity->getName() . " §7a été tué par " . $damagerMinecraftTeamColor . $damager->getName() . " §7!" . $formattedAssisters);
        }
        foreach ($assisters as $assister) {
            $playerAssister = Server::getInstance()->getPlayerByPrefix(Utils::getPlayerName($assister, false));
            if ($playerAssister instanceof Player) {
                $statsApi->add($playerAssister, StatsIds::ASSIST);
                $statsApi->showStatsPopup($playerAssister);
            }
        }
        KitManager::getInstance()->refill($damager);
        $damagerTeam = $this->getPlayerTeam($damager);
        $this->addTeamPoint($damagerTeam);
        if ($this->hasReachedGoal($damagerTeam)) {
            $this->end($damagerTeam);
        }
        MapManager::getInstance()->teleportToTeamSpawn($entity);
        $entity->broadcastSound(new EntityAttackSound());
        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            if ($onlinePlayer instanceof Player) {
                $onlinePlayer->hidePlayer($entity);
            }
        }
        $assistApi->reinitialize($entity);
        Zeno::getInstance()->getScheduler()->scheduleRepeatingTask(new DeathTask($entity), 20);
    }


    /**
     * @return string|null
     */
    public function getGameId(): ?string {
        return $this->gameId;
    }

    /**
     * @param string|null $gameId
     * @return void
     */
    public function setGameId(?string $gameId): void {
        $this->gameId = $gameId;
    }

    /**
     * @return string
     */
    public function generateGameId(): string {
        return uniqid("tdm-");
    }

    /**
     * @param string|Player $player
     * @param int $team
     * @return void
     */
    public function setPlayerTeam(string|Player $player, int $team): void {
        $playerName = Utils::getPlayerName($player, true);
        $playerTeam = $this->getPlayerTeam($player);
        if ($this->hasPlayerTeam($player)) {
            $this->removePlayerFromTeam($player, $playerTeam);
        }
        if ($team > 0) {
            $this->addPlayerToTeam($player, $team);
        }
        $this->playersTeam[$playerName] = $team;
        if ($player instanceof Player) {
            ScoreboardManager::getInstance()->updateTeam($player);
            $this->updateNametag($player);
        }
    }

    /**
     * @param string|Player $player
     * @return void
     */
    public function removePlayerTeam(string|Player $player): void {
        $playerName = Utils::getPlayerName($player, true);
        unset($this->playersTeam[$playerName]);
    }

    /**
     * @param string|Player $player
     * @return int
     */
    public function getPlayerTeam(string|Player $player): int {
        $playerName = Utils::getPlayerName($player, true);
        return $this->hasPlayerTeam($player) ? intval($this->playersTeam[$playerName]) : 0;
    }

    /**
     * @param string|Player $player
     * @return bool
     */
    public function hasPlayerTeam(string|Player $player): bool {
        $playerName = Utils::getPlayerName($player, true);
        return isset($this->playersTeam[$playerName]) && intval($this->playersTeam[$playerName]) > 0;
    }

    /**
     * @param int $team
     * @return int
     */
    public function countTeamPlayers(int $team): int {
        return count($this->getTeamPlayers($team));
    }

    /**
     * @param int $team
     * @return string
     */
    public function getAverageTeamLeague(int $team): string {
        $globalElo = 0;
        $permanentStatsApi = Zeno::getInstance()->getStatsApi();
        $eloApi = $permanentStatsApi->getEloManager();
        $leagueApi = $permanentStatsApi->getLeagueManager();
        $teamPlayers = $this->getTeamPlayers($team);
        foreach ($teamPlayers as $teamPlayer) {
            $playerElo = $eloApi->get($teamPlayer);
            $globalElo += $playerElo;
        }
        $averageElo = intval($globalElo / $this->countTeamPlayers($team));
        return $leagueApi->getLeagueByNecessaryElo($averageElo);
    }

    /**
     * @return void
     */
    public function randomizeTeam(): void {
        $this->teams[1]["players"] = [];
        $this->teams[2]["players"] = [];
        $playersTeamKeys = array_keys($this->playersTeam);
        shuffle($this->playersTeam);
        $finalPlayersTeam = array_combine($playersTeamKeys, $this->playersTeam);
        $this->playersTeam = $finalPlayersTeam;
        foreach ($finalPlayersTeam as $player => $team) {
            $this->playersTeam[$player] = 0;
            $firstTeamPlayersCount = $this->countTeamPlayers(1);
            $secondTeamPlayersCount = $this->countTeamPlayers(2);
            $randomTeam = ($firstTeamPlayersCount !== $secondTeamPlayersCount)
                ? ($firstTeamPlayersCount < $secondTeamPlayersCount ? 1 : 2)
                : mt_rand(1, 2);
            $this->setPlayerTeam($player, $randomTeam);
        }
        $firstTeamPlayers = $this->getTeamPlayers(1);
        $secondTeamPlayers = $this->getTeamPlayers(2);
        $firstTeamFormattedColorName = $this->getFormattedColorNameByColorId(1);
        $secondTeamFormattedColorName = $this->getFormattedColorNameByColorId(2);
        Server::getInstance()->broadcastMessage("§r§l§8» §r" . $firstTeamFormattedColorName . " §l§8«");
        Server::getInstance()->broadcastMessage(!empty($firstTeamPlayers) ? implode("§f, ", $firstTeamPlayers) : "§7Aucun joueur");
        Server::getInstance()->broadcastMessage("§r§l§8» §r" . $secondTeamFormattedColorName . " §l§8«");
        Server::getInstance()->broadcastMessage(!empty($secondTeamPlayers) ? implode("§f, ", $secondTeamPlayers) : "§7Aucun joueur");
        ScoreboardManager::getInstance()->updateTeam(null);
        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            Utils::playSound($onlinePlayer, "mob.enderdragon.flap");
            $playerTeam = $this->getPlayerTeam($onlinePlayer);
            $playerTeamColor = $this->getTeamColor($playerTeam);
            $teamColorName = $this->getColorNameByColorId($playerTeamColor);
            $formattedTeamName = $this->getFormattedColorNameByColorId($playerTeam);
            $onlinePlayer->sendTitle("§r§l§8» §r" . $formattedTeamName . " §l§8«");
            $onlinePlayer->sendSubTitle("§7Votre équipe a été aléatoirement défini à " . $teamColorName . " !");
            $this->updateNametag($onlinePlayer);
        }
    }

    /**
     * @param Player $player
     * @return void
     */
    public function showTeamSelectorMenu(Player $player): void {
        $i = 1;
        $invMenu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $invMenu->setName("§r§l§q» §r§aSélecteur d'équipe §l§q«");
        $invMenuInventory = $invMenu->getInventory();
        $invMenu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $invMenuTransaction) use ($invMenuInventory) {
            $player = $invMenuTransaction->getPlayer();
            $itemSlot = $invMenuTransaction->getAction()->getSlot();
            if (in_array($itemSlot, [20, 24])) {
                $team = match ($itemSlot) {
                    20 => 1,
                    24 => 2
                };
                if ($this->hasPlayerTeam($player)) {
                    if ($this->getPlayerTeam($player) !== $team) {
                        if (!$this->hasFullTeam($team)) {
                            $this->setPlayerTeam($player, $team);
                            $player->removeCurrentWindow();
                        } else {
                            Utils::playSound($player, "mob.zombie.woodbreak");
                        }
                    } else {
                        Utils::playSound($player, "mob.zombie.woodbreak");
                    }
                } else {
                    if (!$this->hasFullTeam($team)) {
                        $this->setPlayerTeam($player, $team);
                        $teamColor = $this->getTeamColor($team);
                        $teamMinecraftColor = $this->getMinecraftColorByColorId($teamColor);
                        $teamColorName = $this->getColorNameByColorId($teamColor);
                        $player->sendMessage(Constants::PREFIX . "§fVous venez de rejoindre l'équipe " . $teamMinecraftColor . $teamColorName . " §f!");
                        $player->removeCurrentWindow();
                    } else {
                        Utils::playSound($player, "mob.zombie.woodbreak");
                    }
                }
            } else {
                Utils::playSound($player, "mob.zombie.woodbreak");
            }
        }));
        foreach ([0, 1, 7, 8, 9, 17, 36, 44, 45, 46, 52, 53] as $border) {
            $borderItem = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::BLACK())->asItem();
            $borderItem->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(-1)));
            $borderItem->setCustomName(TextFormat::colorize(" "));
            $borderItem->setLore([TextFormat::colorize(" ")]);
            $invMenuInventory->setItem($border, $borderItem);
        }
        foreach ([20, 24] as $teamSlot) {
            $teamColor = $this->getTeamColor($i);
            $teamDyeColor = $this->getDyeColorByColorId($teamColor);
            $teamName = $this->getColorNameByColorId($teamColor);
            $teamMinecraftColor = $this->getMinecraftColorByColorId($teamColor);
            $teamItem = VanillaBlocks::WOOL()->setColor($teamDyeColor)->asItem()->setCount(1);
            $teamPlayers = $this->getTeamPlayers($i);
            $formattedItemPlayersList = function () use ($teamPlayers): string {
                $format = null;
                foreach ($teamPlayers as $teamPlayer) {
                    $format .=  TextFormat::EOL . TextFormat::RESET . TextFormat::WHITE . $teamPlayer;
                }
                return $format ?? TextFormat::EOL . "§7Aucun joueur";
            };
            $teamItem->setCustomName("§r§l§8» §r" . $teamMinecraftColor . $teamName . " §l§8«§r" . $formattedItemPlayersList());
            $invMenuInventory->setItem($teamSlot, $teamItem);
            $i++;
        }
        $invMenu->send($player);
    }


    /**
     * @param int $team
     * @param string $color
     * @return void
     */
    public function setTeamColor(int $team, string $color): void {
        $this->teams[$team]["color"] = $color;
    }

    /**
     * @param int $team
     * @return string
     */
    public function getTeamColor(int $team): string {
        return $this->teams[$team]["color"] ?? ColorIds::WHITE;
    }

    /**
     * @param int $team
     * @param int $amount
     * @return void
     */
    public function addTeamPoint(int $team, int $amount = 1): void {
        $this->teams[$team]["points"] += $amount;
    }

    /**
     * @param int $team
     * @return int
     */
    public function getTeamPoints(int $team): int {
        return $this->teams[$team]["points"];
    }

    /**
     * @param int $team
     * @return array
     */
    public function getTeamPlayers(int $team): array {
        return $this->teams[$team]["players"];
    }

    /**
     * @param string|Player $player
     * @param int $team
     * @return void
     */
    public function addPlayerToTeam(string|Player $player, int $team): void {
        $playerName = Utils::getPlayerName($player, true);
        $this->teams[$team]["players"][] = $playerName;
    }

    /**
     * @param string|Player $player
     * @param int $team
     * @return void
     */
    public function removePlayerFromTeam(string|Player $player, int $team): void {
        $playerName = Utils::getPlayerName($player, true);
        unset($this->teams[$team]["players"][array_search($playerName, $this->teams[$team]["players"])]);
    }

    /**
     * @return void
     */
    public function resetTeams(): void {
        $this->teams = [
            1 => [
                "color" => null,
                "points" => 0,
                "players" => []
            ],
            2 => [
                "color" => null,
                "points" => 0,
                "players" => []
            ]
        ];
    }

    /**
     * @return void
     */
    public function resetTeamPlayers(): void {
        $this->teams[1]["players"] = [];
        $this->teams[2]["players"] = [];
        foreach ($this->playersTeam as $player => $team) {
            $this->playersTeam[$player] = 0;
        }
        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $this->updateNametag($onlinePlayer);
        }
    }

    /**
     * @param Player $player1
     * @param Player $player2
     * @return bool
     */
    public function isInSameTeam(Player $player1, Player $player2): bool {
        return $this->getPlayerTeam($player1) === $this->getPlayerTeam($player2);
    }

    /**
     * @param int $team
     * @return bool
     */
    public function hasFullTeam(int $team): bool {
        return count($this->getTeamPlayers($team)) >= $this->getTeamPlayersLimit();
    }

    /**
     * @param int $team
     * @param Player $player
     * @param string $message
     * @return void
     * @noinspection PhpDeprecationInspection
     */
    public function sendMessageToAllTeamPlayers(int $team, Player $player, string $message): void {
        $playersTeam = $this->getTeamPlayers($team);
        foreach ($playersTeam as $playerTeam) {
            $teamPlayer = Server::getInstance()->getPlayerByPrefix(Utils::getPlayerName($playerTeam, false));
            if ($teamPlayer instanceof Player) {
                $teamPlayer->sendMessage("§8[§aÉquipe§8]" . RankManager::getInstance()->formatChatMessage($player, $message));
            }
        }
    }

    /**
     * @param int $status
     * @return void
     */
    public function setStatus(int $status): void {
        $this->status = $status;
        $formattedStatus = match ($status) {
            self::WAITING_STATUS => "false",
            self::LAUNCH_STATUS => "true",
            self::END_STATUS => "ended"
        };
        WebApiManager::getInstance()->setGameStatus($formattedStatus);
    }

    /**
     * @return int
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isWaiting(): bool {
        return $this->status === self::WAITING_STATUS;
    }

    /**
     * @return bool
     */
    public function isLaunched(): bool {
        return $this->status === self::LAUNCH_STATUS;
    }

    /**
     * @return bool
     */
    public function isEnded(): bool {
        return $this->status === self::END_STATUS;
    }

    /**
     * @param int|null $goal
     * @return void
     */
    public function setGoal(?int $goal): void {
        $this->goal = $goal;
    }

    /**
     * @return int|null
     */
    public function getGoal(): ?int {
        return $this->goal;
    }

    /**
     * @param int $team
     * @return bool
     */
    public function hasReachedGoal(int $team): bool {
        return $this->getTeamPoints($team) >= $this->getGoal();
    }

    /**
     * @param int $time
     * @return void
     */
    public function setRemainingTime(int $time): void {
        $this->time = $time;
    }

    /**
     * @return int
     */
    public function getRemainingTime(): int {
        return $this->time;
    }

    /**
     * @return void
     */
    public function decrementTime(): void {
        $this->time--;
    }

    /**
     * @return bool
     */
    public function isTimeUp(): bool {
        return $this->time <= 0;
    }


    /**
     * @return int
     */
    public function getTeamPlayersLimit(): int {
        return $this->teamLimit;
    }

    /**
     * @param int $teamLimit
     */
    public function setTeamPlayersLimit(int $teamLimit): void {
        $this->teamLimit = $teamLimit;
        WebApiManager::getInstance()->setMaxPlayers($teamLimit);
    }

    /**
     * @return int|null
     */
    public function getWinnerTeam(): ?int {
        return $this->winnerTeam;
    }

    /**
     * @param int|null $winnerTeam
     */
    public function setWinnerTeam(?int $winnerTeam): void {
        $this->winnerTeam = $winnerTeam;
    }

    /**
     * @return string
     */
    public function getMap(): string {
        return $this->map;
    }

    /**
     * @param string $map
     */
    public function setMap(string $map): void {
        $this->map = $map;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function isDeath(Player $player): bool {
        return $player->hasNoClientPredictions();
    }

    /**
     * @param Player $player
     * @return void
     */
    public function updateNametag(Player $player): void {
        if ($this->hasPlayerTeam($player)) {
            $playerTeam = $this->getPlayerTeam($player);
            $playerTeamColor = $this->getTeamColor($playerTeam);
            $minecraftTeamColor = $this->getMinecraftColorByColorId($playerTeamColor);
            $player->setNameTag($minecraftTeamColor . $player->getName());
        } else {
            $player->setNameTag(TextFormat::GRAY . TextFormat::ITALIC . $player->getName());
        }
    }

    /**
     * @param string $colorId
     * @return string
     */
    public function getColorNameByColorId(string $colorId): string {
        return match ($colorId) {
            ColorIds::RED => "Rouge",
            ColorIds::ORANGE => "Orange",
            ColorIds::YELLOW => "Jaune",
            ColorIds::LIME => "Vert",
            ColorIds::AQUA => "Aqua",
            ColorIds::BLUE => "Bleu",
            ColorIds::PURPLE => "Violet",
            ColorIds::PINK => "Rose",
            ColorIds::WHITE => "Blanc",
            ColorIds::BLACK => "Noir"
        };
    }

    /**
     * @param string $colorId
     * @return string
     */
    public function getMinecraftColorByColorId(string $colorId): string {
        return match ($colorId) {
            ColorIds::RED => "§c",
            ColorIds::ORANGE => "§6",
            ColorIds::YELLOW => "§e",
            ColorIds::LIME => "§a",
            ColorIds::AQUA => "§b",
            ColorIds::BLUE => "§9",
            ColorIds::PURPLE => "§5",
            ColorIds::PINK => "§d",
            ColorIds::WHITE => "§f",
            ColorIds::BLACK => "§8"
        };
    }

    /**
     * @param int $team
     * @return string
     */
    public function getFormattedColorNameByColorId(int $team): string {
        $teamColor = $this->getTeamColor($team);
        $colorName = $this->getColorNameByColorId($teamColor);
        $minecraftColor = $this->getMinecraftColorByColorId($teamColor);
        return $minecraftColor . $colorName;
    }

    /**
     * @param string $colorId
     * @return Color
     */
    public function getColorByColorId(string $colorId): Color {
        return match ($colorId) {
            ColorIds::RED => new Color(255, 85, 85),
            ColorIds::ORANGE => new Color(255, 170, 0),
            ColorIds::YELLOW => new Color(255, 255, 85),
            ColorIds::LIME => new Color(85, 255, 85),
            ColorIds::AQUA => new Color(85, 255, 255),
            ColorIds::BLUE => new Color(85, 85, 255),
            ColorIds::PURPLE => new Color(170, 0, 170),
            ColorIds::PINK => new Color(255, 85, 255),
            ColorIds::WHITE => new Color(255, 255, 255),
            ColorIds::BLACK => new Color(0, 0, 0)
        };
    }

    /**
     * @param string $colorId
     * @return DyeColor
     */
    public function getDyeColorByColorId(string $colorId): DyeColor {
        return match ($colorId) {
            ColorIds::RED => DyeColor::RED(),
            ColorIds::ORANGE => DyeColor::ORANGE(),
            ColorIds::YELLOW => DyeColor::YELLOW(),
            ColorIds::LIME => DyeColor::LIME(),
            ColorIds::AQUA => DyeColor::LIGHT_BLUE(),
            ColorIds::BLUE => DyeColor::BLUE(),
            ColorIds::PURPLE => DyeColor::PURPLE(),
            ColorIds::PINK => DyeColor::PINK(),
            ColorIds::WHITE => DyeColor::WHITE(),
            ColorIds::BLACK => DyeColor::GRAY()
        };
    }

    /**
     * @param string $colorId
     * @return int
     */
    public function getColorId(string $colorId): int {
        return match ($colorId) {
            ColorIds::RED => 0,
            ColorIds::ORANGE => 1,
            ColorIds::YELLOW => 2,
            ColorIds::LIME => 3,
            ColorIds::AQUA => 4,
            ColorIds::BLUE => 5,
            ColorIds::PURPLE => 6,
            ColorIds::PINK => 7,
            ColorIds::WHITE => 8,
            ColorIds::BLACK => 9
        };
    }

    /**
     * @return array
     */
    public function getColoredColors(): array {
        return ["§cRouge", "§6Orange", "§eJaune", "§aVert", "§bAqua", "§9Bleu", "§5Violet", "§dRose", "§fBlanc", "§8Noir"];
    }

    /**
     * @return array
     */
    public function getColors(): array {
        return ColorIds::COLORS;
    }

}
