<?php

namespace Migration\Api;

use Migration\Api\ConfManager;
use \Migration\App\Config\ConfEntryValuesEnum;
use \Exception;

/**
 * Notifies the operator of the migration failure.
 */
class FailureNotifier {

  public static function notify($message) {
    $confMgr = ConfManager::getInstance();

    $recipientAddress = $confMgr->getConfEntryValue(ConfEntryValuesEnum::AdminEmail);
    echo $recipientAddress;
    $subject = 'An error occured during migration';
    $headers  = 'MIME-Version: 1.0' . "\n";
    $headers .= 'To: Admin <'. $recipientAddress .' >' . "\r\n";
    $headers .= "Reply-To: migration_script@noreply.org\n";
    $headers .= 'From: migration_script<migration_script@noreply.org>'."\n";
    mail($recipientAddress, $subject, $message, $headers);
  }

}
