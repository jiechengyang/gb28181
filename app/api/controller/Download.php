<?php


namespace app\api\controller;

use Biz\Record\Service\RecordService;
use support\Request;
use support\Response;

class Download extends \app\AbstractController
{
    public function record(Request $request)
    {
        $id = $request->input('id');
        if (empty($id)) {
            return response('Not Found Record File', 404);
        }
        $recordFile = $this->getRecordService()->getRecordFile($id);
        if (empty($recordFile)) {
            return response('Not Found Record File', 404);
        }

        $headers = @get_headers($recordFile['download_url']);
        if (!$headers || strpos($headers[0], '200') === false) {
            return response('Not Found Record File', 404);
        }

        $urlParams = explode('.', $recordFile['download_url']);
        $ext = end($urlParams);
        if ($ext === 'mp4') {
            $contentType = 'video/mp4';
        } else {
            $contentType = 'application/vnd.apple.mpegurl; charset=utf-8';
        }

        $response = new Response();
        $filesize = filesize($recordFile['video_path']);
        $begin = 0;
        $end = $filesize - 1;
        // webman 不支持下载限速
        $response->withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Content-Range' => "bytes {$begin}-{$end}/$filesize",
//            'Content-Length' => $end - $begin + 1,
            'Cache-Control' => 'max-age=0',
            'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            'Cache-Control' => 'cache, must-revalidate',
        ]);
        return $response
            ->download($recordFile['video_path'], $recordFile['created_time'] . '.' . $ext);
//        try {
//            $fp = fopen($recordFile['download_url'], 'rb');
//        } catch (\Throwable $e) {
//            return response($e->getMessage(), 500);
//        }
//
//        $filesize = $recordFile['file_size'];
//        clearstatcache(true, $recordFile['download_url']);
//        $urlParams = explode('.', $recordFile['download_url']);
//        $ext = end($urlParams);
//        if ($ext === 'mp4') {
//            $contentType = 'video/mp4';
//        } else {
//            $contentType = 'application/vnd.apple.mpegurl; charset=utf-8';
//        }
//
//        $response->withHeaders([
//            ['Content-type', $contentType],
//            ['Accept-Ranges', 'bytes'],
//            ['Accept-Length', $filesize],
//            ['Content-Length', $filesize],
//            ['Content-Disposition', 'attachment; filename=' . $recordFile['created_time'] . '.' . $ext],
//            ['Cache-Control', 'max-age=0'],
//            ['Expires', ' Mon, 26 Jul 1997 05:00:00 GMT'],
//            ['Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT'],
//            ['Cache-Control', 'cache, must-revalidate'],
//            ['Pragma', 'public']
//        ]);
//        $buffer = 2048;
//        $fileCount = 0;
//        ob_start();
//        do {
//            $fileContent = fread($fp, $buffer);
//            $fileCount += $buffer;
//            print $fileContent;
//            ob_flush();
//            flush();
//            usleep(10000 * 0.5);
//        } while (!feof($fp));
//        fclose($fp);
//        $response->withBody(ob_get_contents());
    }

    /**
     * @return RecordService
     */
    protected function getRecordService()
    {
        return $this->getBiz()->service('Record:RecordService');
    }
}