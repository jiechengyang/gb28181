<?php


namespace Biz\AkStreamSdk\Resources;


interface AKStreamKeeper
{
    public function getFFmpegTemplateList();

    public function delFFmpegTemplate();

    public function modifyFFmpegTemplate();

    public function addFFmpegTemplate();

    public function getVersion();

    public function guessAnRtpPort();

    public function deleteFile();

    public function keeperHealth();

    public function fileExists();

    public function deleteFileList();

    public function cleanUpEmptyDir();

    public function startMediaServer();

    public function shutdownMediaServer();

    public function restartMediaServer();

    public function reloadMediaServer();

    public function checkMediaServerRunning();
}