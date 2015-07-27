<?php
  /**
  *
  * @author  Neomar Marcos Bassani <neomar.bassani@e-storageonline.com.br>
  * @param   array  $activity  Contains database values for the tickler record.
  * @return  return true if success, false if not.
  */

  function create_tickler($activity){
    if(empty($activity['tickler_email']) && file_exists(getcwd() . '-custom/extensions/google_drive_connector/index.php')){
      return true;
    }
    
    require_once(getcwd() . '-custom/extensions/google_drive_connector/index.php');
    require_once(getcwd() . '-custom/extensions/create_tickler/config.php');

    $tickler = new PikaDrive('nbassani');

    $event = $tickler->createEvent(
      $activity['tickler_email'],
      formatString(CALENDAR_SUBJECT, $activity),
      formatString(CALENDAR_DESCRIPTION, $activity),
      formatDateTime($activity['act_date'], $activity['act_time']),
      formatDateTime($activity['act_end_date'], $activity['act_end_time'], $activity['act_date'])
    );

    return !empty($event);
  }

  function formatDateTime($date, $time, $initDate = null){
    if(empty($date)){
      $date = $initDate;
    }

    if(!empty($date) && !empty($time)){
      date_default_timezone_set('UTC');
      $d = date("Y-m-d\TH:i:sP", strtotime($date." ".$time));
      return $d;
    }else {
      return null;
    }
  }

  function formatString($str, $v){
      $keys = array(
          '%case%',
          '%clientLastName%',
          '%clientFirstName%',
          '%subject%',
          '%description%',
          '%user%',
          '%caseLink%'
      );

      $c = preg_split('/\s+/', trim($v['client_name']));

      $values = array(
          $v['case_number'],
          array_pop($c),
          array_shift($c),
          $v['summary'],
          $v['notes'],
          '',
          $v['case_link']
      );

      return str_replace($keys,$values,$str);
    }
?>
