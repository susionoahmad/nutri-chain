<?php
$lines = file('d:/PROJECT/nutri-chain/backend/storage/logs/laravel.log');
$last50 = array_slice($lines, -50);
echo implode("", $last50);
