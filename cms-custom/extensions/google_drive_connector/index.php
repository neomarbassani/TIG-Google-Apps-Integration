<?php
require_once 'google-api-php-client/src/Google_Client.php';
require_once 'google-api-php-client/src/contrib/Google_DriveService.php';
require_once 'google-api-php-client/src/contrib/Google_CalendarService.php';
require_once 'google_config.php';

$url_array = explode('?', 'https://'.$_SERVER ['HTTP_HOST'].$_SERVER['REQUEST_URI']);
define("URL", $url_array[0]);
define("TOKENS_PATH", dirname(__FILE__) . '/' . "tokens/");

class PikaDrive {
  private $gClient;
  private $token;
  private $tokenPath = TOKENS_PATH;

  function __construct($username = null){
    $this->gClient = new Google_Client();
    $this->gClient->setClientId(CLIENT_ID);
    $this->gClient->setClientSecret(CLIENT_SECRET);
    $this->gClient->setRedirectUri(URL);
    $this->gClient->setScopes(array('https://www.googleapis.com/auth/drive', 'https://www.googleapis.com/auth/calendar'));

    if($username != null && self::setToken($username)){
      self::authenticate();
    }
  }

  function setToken($username, $authToken = ''){
    $tokenPath = $this->tokenPath . $username;

    if(isset($authToken) && !empty($authToken)){
      $this->token = $this->gClient->authenticate($authToken);
      file_put_contents($tokenPath, $this->token);
    }else{
      if(file_exists($tokenPath)){
        $this->token = file_get_contents($tokenPath);
        return true;
      }
    }

    return false;
  }

  function unauthorize($username){
    unlink($this->tokenPath . $username);
  }

  function authenticate(){
    if (!isset($this->token)) {
      $this->gClient->authenticate();
    } else {
      $this->gClient->setAccessToken($this->token);
    }
  }

  function check(){
    return isset($this->token);
  }

  function createFolder($folderName, $parentId = null){
    if(empty($parentId) && defined('UNIQUE_FOLDER_ID')){
      $parentId = UNIQUE_FOLDER_ID;
    }

    return self::uploadFile("", $folderName, $parentId);
  }

  function uploadFile($filePath = "", $fileName, $folderId = null){
    if($fileName == null){
      $fileName = basename($filePath);
    }
    $data = "";

    $file = new Google_DriveFile();
    $file->setTitle($fileName);
    if($filePath){
      $file->setMimeType('');
      $data = file_get_contents($filePath);
    }else{
      $file->setMimeType('application/vnd.google-apps.folder');
    }

    if ($folderId != null) {
      $folders = explode(",", $folderId);
      $parents = array();
      foreach ($folders as $folder) {
        $parent = new Google_ParentReference();
        $parent->setId($folder);
        array_push($parents, $parent);
      }
      $file->setParents($parents);
    }

    $service = new Google_DriveService($this->gClient);

    $createdFile = $service->files->insert($file, array(
      'data' => $data,
      'mimeType' => ''
    ));

    return $createdFile;
  }

  function generateQueryString($q, $param, $value){
    if($q){
      $q .= " and ";
    }
    $q .= $param;
    return str_replace('?', $value, $q);
  }

  function listFiles($folderId = "", $trashed = false){
    $parameters = "";
    $q = "";
    
    if($folderId)
      $q = self::generateQueryString($q, "'?' in parents", $folderId);
    if(!$trashed)
      $q = self::generateQueryString($q, "trashed = ?", "false");

    $parameters['q'] = $q;

    $service = new Google_DriveService($this->gClient);
    $files = $service->files->listFiles($parameters);

    return $files['items'];
  }
  
  function isAuthenticated($username){
    $tokenPath = $this->tokenPath . $username;

  	return file_exists($tokenPath);
  }

  function createEvent($to, $summary, $description, $startDate, $endDate = null){
    $optParams = array(
      'sendNotifications' => TRUE
    );

    $event = new Google_Event(); 
    $event->setSummary($summary);
    $event->setDescription($description);
    
    $start = new Google_EventDateTime();
    $start->setDateTime($startDate);
    $event->setStart($start);

    if($endDate == null){
      $endDate = $startDate;
    }
    $end = new Google_EventDateTime();
    $end->setDateTime($endDate);
    $event->setEnd($end);

    $attendees = Array();
    foreach ($to as $e) {
      $g_ea = new Google_EventAttendee();
      $g_ea->setEmail($e);
      $attendees[] = $g_ea;
    }
    $event->setAttendees($attendees);

    $service = new Google_CalendarService($this->gClient);
    return $service->events->insert('primary', $event, $optParams);
  }
}
