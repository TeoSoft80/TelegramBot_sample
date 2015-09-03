<?php

set_time_limit(0);

require_once 'EmergenzePrato.php';

define('BOT_TOKEN', '');

//gestisce gli aggiornamenti lenti

$bot = new EmergenzePrato(BOT_TOKEN, 'PollBotChat');
$bot->runLongpoll();
