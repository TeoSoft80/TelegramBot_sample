<?php

require_once 'EmergenzePrato.php';

define('BOT_TOKEN', '');
define('BOT_WEBHOOK', 'https://..../bot-webhook.php');

//istanzia Bot
$bot = new EmergenzePrato(BOT_TOKEN, 'PollBotChat');

//valuta se l'interfaccia è di tipo CLI per vedere il parametro e settare o rimuovere il webhook e poi esce. 
if (php_sapi_name() == 'cli') {
  if ($argv[1] == 'set') {
    $bot->setWebhook(BOT_WEBHOOK);
  } else if ($argv[1] == 'remove') {
    $bot->removeWebhook();
  }
  exit;
}

//legge un file
$response = file_get_contents('php://input');
$update = json_decode($response, true);

$bot->init();
$bot->onUpdateReceived($update);
