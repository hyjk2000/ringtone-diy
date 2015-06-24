<?php

/**
 * Add your routes here
 */
$app->get('/', function () use ($app) {
    $app->assets
        ->collection('styles')
        ->setTargetPath('css/total.css')
        ->setTargetUri('css/total.css?20141216')
        ->addCss('css/bootstrap.min.css')
        ->addCss('css/bootstrap-theme.min.css')
        ->addCss('css/bootstrap-slider.css')
        ->addCss('css/main.css')
        ->join(TRUE)
         ->addFilter(new Phalcon\Assets\Filters\Cssmin());
    $app->assets
        ->collection('scripts')
        ->setTargetPath('js/total.js')
        ->setTargetUri('js/total.js?20141216')
        ->addJs('js/jquery.min.js')
        ->addJs('js/bootstrap-slider.js')
        ->addJs('js/main.js')
        ->join(TRUE)
         ->addFilter(new Phalcon\Assets\Filters\Jsmin());
    echo $app['view']->render('index');
});

$app->post('/upload', function () use ($app, $config) {
    if ($app->request->hasFiles()) {
        foreach ($app->request->getUploadedFiles() as $file) {
            $mimeType = $file->getRealType();
            if (strripos($mimeType, 'audio/') === 0) {
                $originName = $file->getName();
                $filepath = $config->application->temp;
                $filename = md5($app->request->getClientAddress() . '|' . time());
                $file->moveTo("{$filepath}{$filename}");
                @exec("ffprobe -v quiet -print_format json -show_format '{$filepath}{$filename}'", $output, $return_value);
                if ($return_value === 0) {
                    $fileinfo = json_decode(implode(NULL, $output));
                    $answer = array(
                        'filename' => $filename,
                        'originName' => $originName,
                        'duration' => floor($fileinfo->format->duration),
                    );
                    echo json_encode($answer);
                    return TRUE;
                }
            }
        }
    }
    $app->response->setStatusCode(400, 'Bad Request')->sendHeaders();
    echo '{}';
    return FALSE;
});

$app->post('/render', function () use ($app, $config) {
    $filename = $app->request->getPost('filename');
    $originName = $app->request->getPost('originName');
    $originName = mb_substr($originName, 0, mb_strrpos($originName, '.'));
    list($ss, $to) = explode(',', $app->request->getPost('range'));
    $ss = (int)$ss;
    $to = (int)$to;
    $filepath = $config->application->temp;
    if (!file_exists($filepath . $filename) || $ss >= $to) {
        $app->response->setStatusCode(400, 'Bad Request')->sendHeaders();
        return FALSE;
    }
    @exec("ffmpeg -i '{$filepath}{$filename}' -c:a libfdk_aac -ss {$ss} -to {$to} -vn -sn -y '{$filepath}{$filename}.m4a'", $output, $return_value);
    if ($return_value === 0) {
        $file_to_send = "{$filepath}{$filename}.m4a";
        $app->response
            ->setContentType('audio/MP4A-LATM')
            ->setHeader('Content-Length', filesize($file_to_send))
            ->setFileToSend($file_to_send, "{$originName}.m4r");
        return $app->response;
    } else {
        $app->response->setStatusCode(500, 'Internal Server Error')->sendHeaders();
        echo '生成铃声失败，请刷新页面重试';
    }
    return TRUE;
});

/**
 * Not found handler
 */
$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, 'Not Found')->sendHeaders();
    echo $app['view']->render('404');
});
