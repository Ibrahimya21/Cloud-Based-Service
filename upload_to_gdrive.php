<?php
require_once __DIR__ . '/vendor/autoload.php';

function uploadToDrive($filePath, $fileName) {
    $client = new Google_Client();
    $client->setAuthConfig('credentials.json');
    $client->addScope(Google_Service_Drive::DRIVE_FILE);
    $client->setAccessType('offline');

    // تحميل التوكن المحفوظ
    if (!file_exists('token.json')) {
        throw new Exception("ملف التوكن غير موجود. يرجى التفويض أولاً.");
    }

    $accessToken = json_decode(file_get_contents('token.json'), true);
    $client->setAccessToken($accessToken);

    if ($client->isAccessTokenExpired()) {
        throw new Exception("انتهت صلاحية التوكن. يرجى إعادة التفويض.");
    }

    $service = new Google_Service_Drive($client);

    $fileMetadata = new Google_Service_Drive_DriveFile([
        'name' => $fileName
    ]);

    $content = file_get_contents($filePath);

    $file = $service->files->create($fileMetadata, [
        'data' => $content,
        'uploadType' => 'multipart',
        'fields' => 'id'
    ]);

    return $file->id;
}
