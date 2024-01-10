<?php

namespace tdm\utils\ids;

interface RankIds {

    public const PLAYER = "joueur";
    public const HOSTER = "hoster";
    public const ADMIN = "admin";

    public const ALL_RANKS = [
        self::PLAYER,
        self::HOSTER,
        self::ADMIN
    ];

}
