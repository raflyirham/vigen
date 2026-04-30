<?php

$arguments = array_slice($_SERVER['argv'], 1);
file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'arguments.json', json_encode($arguments, JSON_THROW_ON_ERROR));
file_put_contents($arguments[array_key_last($arguments)], 'merged-video');

exit(0);