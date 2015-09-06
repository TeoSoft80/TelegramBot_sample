<?php

 abstract class TelegramBotCore {

  protected $host;
  protected $port;
  protected $apiUrl;

  public    $botId;
  public    $botUsername;
  protected $botToken;

  protected $handle;
  protected $inited = false;

  protected $lpDelay = 1;
  protected $netDelay = 1;

  protected $updatesOffset = false;
  protected $updatesLimit = 30;
  protected $updatesTimeout = 10;

  protected $netTimeout = 10;
  protected $netConnectTimeout = 5;

  public function __construct($token, $options = array()) {
    $options += array(
      'host' => 'api.telegram.org',
      'port' => 443,
    );

    $this->host = $host = $options['host'];
    $this->port = $port = $options['port'];
    $this->botToken = $token;

    $proto_part = ($port == 443 ? 'https' : 'http');
    $port_part = ($port == 443 || $port == 80) ? '' : ':'.$port;

    $this->apiUrl = "{$proto_part}://{$host}{$port_part}/bot{$token}";
  }

  public function init() {
    if ($this->inited) {
      return true;
    }

    $this->handle = curl_init();

    $response = $this->request('getMe');
    if (!$response['ok']) {
      throw new Exception("Can't connect to server");
    }

    $bot = $response['result'];
    $this->botId = $bot['id'];
    $this->botUsername = $bot['username'];

    $this->inited = true;
    return true;
  }

  public function runLongpoll() {
    $this->init();
    $this->longpoll();
  }

  public function setWebhook($url) {
    $this->init();
    $result = $this->request('setWebhook', array('url' => $url));
    return $result['ok'];
  }

  public function removeWebhook() {
    $this->init();
    $result = $this->request('setWebhook', array('url' => ''));
    return $result['ok'];
  }

  public function request($method, $params = array(), $options = array()) {
    $options += array(
      'http_method' => 'GET',
      'timeout' => $this->netTimeout,
    );
    $params_arr = array();
    foreach ($params as $key => &$val) {
      if (!is_numeric($val) && !is_string($val)) {
        $val = json_encode($val);
      }
      $params_arr[] = urlencode($key).'='.urlencode($val);
    }
    $query_string = implode('&', $params_arr);

    $url = $this->apiUrl.'/'.$method;

    if ($options['http_method'] === 'POST') {
      curl_setopt($this->handle, CURLOPT_SAFE_UPLOAD, false);
      curl_setopt($this->handle, CURLOPT_POST, true);
      curl_setopt($this->handle, CURLOPT_POSTFIELDS, $query_string);
    } else {
      $url .= ($query_string ? '?'.$query_string : '');
      curl_setopt($this->handle, CURLOPT_HTTPGET, true);
    }

    $connect_timeout = $this->netConnectTimeout;
    $timeout = $options['timeout'] ?: $this->netTimeout;

    curl_setopt($this->handle, CURLOPT_URL, $url);
    curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->handle, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
    curl_setopt($this->handle, CURLOPT_TIMEOUT, $timeout);

    $response_str = curl_exec($this->handle);
    $errno = curl_errno($this->handle);
    $http_code = intval(curl_getinfo($this->handle, CURLINFO_HTTP_CODE));

    if ($http_code == 401) {
      throw new Exception('Invalid access token provided');
    } else if ($http_code >= 500 || $errno) {
      sleep($this->netDelay);
      if ($this->netDelay < 30) {
        $this->netDelay *= 2;
      }
    }

    $response = json_decode($response_str, true);

    return $response;
  }

  protected function longpoll() {
    $params = array(
      'limit' => $this->updatesLimit,
      'timeout' => $this->updatesTimeout,
    );
    if ($this->updatesOffset) {
      $params['offset'] = $this->updatesOffset;
    }
    $options = array(
      'timeout' => $this->netConnectTimeout + $this->updatesTimeout + 2,
    );
    $response = $this->request('getUpdates', $params, $options);
    if ($response['ok']) {
      $updates = $response['result'];
      if (is_array($updates)) {
        foreach ($updates as $update) {
          $this->updatesOffset = $update['update_id'] + 1;
          $this->onUpdateReceived($update);
        }
      }
    }
    $this->longpoll();
  }

  abstract public function onUpdateReceived($update);

}

class TelegramBot extends TelegramBotCore {

  protected $chatClass;
  protected $chatInstances = array();

  public function __construct($token, $chat_class, $options = array()) {
    parent::__construct($token, $options);

    $instance = new $chat_class($this, 0);
    if (!($instance instanceof TelegramBotChat)) {
      throw new Exception('ChatClass must be extends TelegramBotChat');
    }
    $this->chatClass = $chat_class;
  }

  public function onUpdateReceived($update) {
    if ($update['message']) {
      $text = $update['message'];
      $chat_id = intval($message['chat']['id']);

	if ($text == "/start") {
			create_keyboard($this,$chat_id);
			$log=$today. ";new chat started;" .$chat_id. "\n";
			file_put_contents($logfile, $log, FILE_APPEND | LOCK_EX);
		}
		//richiedi previsioni meteo di oggi
		elseif ($text == "/meteo" || $text == "meteo") {
			$reply = "Previsioni Meteo per oggi " .$data->lamma_text("oggi").$data->biometeo_text("oggi");
			$content = array('chat_id' => $chat_id, 'text' => $reply);
			$this->request('sendMessage',$content);
			$log=$today. ";meteo sent;" .$chat_id. "\n";
			file_put_contents($logfile, $log, FILE_APPEND | LOCK_EX);
			//aggiorna tastiera
			create_keyboard($this,$chat_id);	
			}
		//richiede previsioni meteo di domani
		elseif ($text == "/previsioni" || $text == "previsioni") {
			$reply = "Previsioni Meteo per domani " .$data->lamma_text("domani").$data->biometeo_text("domani");
			$content = array('chat_id' => $chat_id, 'text' => $reply);
			$this->request('sendMessage',$content);
			$log=$today. ";previsioni sent;" .$chat_id. "\n";
			file_put_contents($logfile, $log, FILE_APPEND | LOCK_EX);
			//aggiorna tastiera
		   create_keyboard($this,$chat_id);
		}
		//richiede rischi di oggi a Prato
		elseif ($text == "/rischi" || $text == "rischi") {
			$reply = "Rischi di oggi:\r\n".$data->risk_text("oggi","B").$data->risk_text("oggi","R1");
			$content = array('chat_id' => $chat_id, 'text' => $reply);
			$this->request('sendMessage',$content);
			$log=$today. ";rischi sent;" .$chat_id. "\n";
			file_put_contents($logfile, $log, FILE_APPEND | LOCK_EX);
			//aggiorna tastiera
			create_keyboard($this,$chat_id);
		}
		//crediti
		elseif ($text == "/crediti" || $text == "crediti") {
			 $reply = "Applicazione sviluppata da Matteo Tempestini, dettagli e fonti dei dati presenti su : http://pratosmart.teo-soft.com/emergenzeprato/";
			 $content = array('chat_id' => $chat_id, 'text' => $reply);
			 $this->request('sendMessage',$content);
			 $log=$today. ";crediti sent;" .$chat_id. "\n";
			 file_put_contents($logfile, $log, FILE_APPEND | LOCK_EX);
			 //aggiorna tastiera
			 create_keyboard($this,$chat_id);
		}
		//richiede la temperatura
		elseif ($text == "/temperatura" || $text == "temperatura") {
	 
			 create_keyboard_temp($this,$chat_id);	
		}
		elseif ($text =="Prato" || $text == "/temp-prato")
		{
			 $reply = "Temperatura misurata in zona Prato Est : " .$data->get_temperature("prato est");
			 $content = array('chat_id' => $chat_id, 'text' => $reply);
			$this->request('sendMessage',$content);
			 $log=$today. ";temperatura Prato sent;" .$chat_id. "\n";
			 file_put_contents($logfile, $log, FILE_APPEND | LOCK_EX);
			 //aggiorna tastiera
			 create_keyboard($this,$chat_id);
		}
		elseif ($text =="Vaiano/Sofignano" || $text == "/temp-vaianosofignano")
		{
			 $reply = "Temperatura misurata in zona Vaiano/Sofignano : " .$data->get_temperature("vaiano sofignano");
			 $content = array('chat_id' => $chat_id, 'text' => $reply);
			$this->request('sendMessage',$content);
			 $log=$today. ";temperatura Vaiano/Sofignano sent;" .$chat_id. "\n";
			 file_put_contents($logfile, $log, FILE_APPEND | LOCK_EX);
			 //aggiorna tastiera
			 create_keyboard($this,$chat_id);
		}
		elseif ($text =="Vaiano/Schignano" || $text == "/temp-vaianoschignano")
		{
			 $reply = "Temperatura misurata in zona Vaiano/Schignano : " .$data->get_temperature("vaiano schignano");
			 $content = array('chat_id' => $chat_id, 'text' => $reply);
			$this->request('sendMessage',$content);
			 $log=$today. ";temperatura Vaiano/Schignano sent;" .$chat_id. "\n";
			 file_put_contents($logfile, $log, FILE_APPEND | LOCK_EX);
			 //aggiorna tastiera
			 create_keyboard($this,$chat_id);
		}
		elseif ($text =="Montepiano/Vernio" || $text == "/temp-montepianovernio")
		{
			 $reply = "Temperatura misurata in zona Montepiano/Vernio : " .$data->get_temperature("montepiano vernio");
			 $content = array('chat_id' => $chat_id, 'text' => $reply);
			$this->request('sendMessage',$content);
			 $log=$today. ";temperatura Montepiano/Vernio sent;" .$chat_id. "\n";
			 file_put_contents($logfile, $log, FILE_APPEND | LOCK_EX);
			 //aggiorna tastiera
			 create_keyboard($this,$chat_id);
		}
		//comando errato
		else{
			 $reply = "Hai selezionato un comando non previsto. Per informazioni visita : http://pratosmart.teo-soft.com/emergenzeprato/";
			 $content = array('chat_id' => $chat_id, 'text' => $reply);
			$this->request('sendMessage',$content);
			 $log=$today. ";wrong command sent;" .$chat_id. "\n";
			 file_put_contents($logfile, $log, FILE_APPEND | LOCK_EX);	
		 }
    }
  }


// Crea la tastiera
private function create_keyboard($telegram, $chat_id)
{
		$option = array(["meteo","previsioni"],["rischi", "temperatura"],["crediti"]);
    	$keyb = $telegram->buildKeyBoard($option, $onetime=false);
		$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "Seleziona un'opzione per essere aggiornato");
		$telegram->request('sendMessage',$content);
}

//crea la tastiera per scegliere la zona temperatura
private function create_keyboard_temp($telegram, $chat_id)
{
		$option = array(["Prato","Vaiano/Sofignano"],["Vaiano/Schignano", "Montepiano/Vernio"]);
    	$keyb = $telegram->buildKeyBoard($option, $onetime=false);
    	$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "Seleziona la zona di cui vuoi sapere la temperatura");
		$telegram->request('sendMessage',$content);

}

private function buildKeyBoard(array $options, $onetime = true, $resize = true, $selective = true) {
        $replyMarkup = array(
            'keyboard' => $options,
            'one_time_keyboard' => $onetime,
            'resize_keyboard' => $resize,
            'selective' => $selective
        );
        $encodedMarkup = json_encode($replyMarkup, true);
        return $encodedMarkup;
}

  protected function getChatInstance($chat_id) {
    if (!isset($this->chatInstances[$chat_id])) {
      $instance = new $this->chatClass($this, $chat_id);
      $this->chatInstances[$chat_id] = $instance;
      $instance->init();
    }
    return $this->chatInstances[$chat_id];
  }
}



abstract class TelegramBotChat {

  protected $core;
  protected $chatId;
  protected $isGroup;

  public function __construct($core, $chat_id) {
    if (!($core instanceof TelegramBot)) {
      throw new Exception('$core must be TelegramBot instance');
    }
    $this->core = $core;
    $this->chatId = $chat_id;
    $this->isGroup = $chat_id < 0;
  }

  public function init() {}

  public function bot_added_to_chat($message) {}
  public function bot_kicked_from_chat($message) {}
//public function command_commandname($params, $message) {}
  public function some_command($command, $params, $message) {}
  public function message($text, $message) {}

  protected function apiSendMessage($text, $params = array()) {
    $params += array(
      'chat_id' => $this->chatId,
      'text' => $text,
    );
    return $this->core->request('sendMessage', $params);
  }

}
?>
