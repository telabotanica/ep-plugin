<?php

namespace Migration\App\Config;

//require_once(dirname(__FILE__) . "/../App/ConfEntryValuesEnum.php");
//require_once("/home/slack/Desktop/PRJZ/TB-FREELANCE/IMPORT/refactoring4/Migration/ConfEntryValuesEnum.php");

abstract class ConfEntryValuesEnum {
    const WpDir      = "wpdir";
    const AdminEmail = "adminemail";
}

const CONF = [
  ConfEntryValuesEnum::WpDir      => "/worpdress/dir",
  ConfEntryValuesEnum::AdminEmail => "",
];
