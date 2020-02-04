<?php

// The order matters.
include(realpath(dirname(__FILE__)).'/modules/Logs.php');
include(realpath(dirname(__FILE__)).'/modules/Auth.php');
include(realpath(dirname(__FILE__)).'/modules/Callbacks.php');
include(realpath(dirname(__FILE__)).'/modules/Channels.php');
include(realpath(dirname(__FILE__)).'/modules/Rotations.php');
include(realpath(dirname(__FILE__)).'/modules/Adapters.php');