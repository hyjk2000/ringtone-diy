<?php

return new \Phalcon\Config(array(
    'application' => array(
		'modelsDir' => APP_PATH . '/models/',
		'viewsDir'  => APP_PATH . '/views/',
		'temp'      => APP_PATH . '/temp/',
		'ffmpeg'    => 'ffmpeg',
		'ffprobe'   => 'ffprobe',
		'baseUri'   => '/',
    )
));
