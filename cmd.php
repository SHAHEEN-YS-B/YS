<?php

require realpath(__DIR__) . '/includes.php';

set_language_by_code('ar_AR');
echo json_encode(command_list());
