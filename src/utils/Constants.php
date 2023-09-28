<?php

namespace zenogames\utils;

interface Constants {

    public const NAME = "§qTDM";
    public const PREFIX = "§l§q» §r";

    public const ENDED_MAP = "waiting";
    public const WAITING_MAP = "lobby";

    public const WEB_API_URL = "http://45.145.166.136:3000/";
    public const WEB_ADD_PLAYER_ENDPOINT = "addDiscord?username={name}&idDiscord={id}";
    public const WEB_ADD_PLAYER_TEAM_ENDPOINT = "add{team}";
    public const WEB_SET_MAX_PLAYERS_ENDPOINT = "game?setMax={count}";
    public const WEB_GAME_STATUS_ENDPOINT = "game?started={status}";
    public const WEB_RESET_GAME_ENDPOINT = "game?clear=true";

}
