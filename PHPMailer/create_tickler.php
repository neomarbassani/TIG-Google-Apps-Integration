<?php
  require "email.php";
  /**
  *
  * @author  Neomar Marcos Bassani <neomar.bassani@e-storageonline.com.br>
  * @param   array  $activity  Contains database values for the tickler record.
  * @return  return true if success, false if not.
  */

  function create_tickler($activity){
    $mail = new PikaEmail;

    $begin = strtotime($activity['act_date']." ".$activity['act_time']);
    if(isset($activity['act_end_date'])){
      $end = strtotime($activity['act_end_date']." ".$activity['act_end_time']);
    }else $end = $begin;

    $mail->generateEmailContent($activity['case_number'], $activity['case_link'], $begin, $end);
    $mail->formatSubject($activity);
    return $mail->send($activity['tickler_email']);
  }

?>