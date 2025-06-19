<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once 'vendor/autoload.php';
require_once 'upload_to_gdrive.php';

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$response = ['success' => false, 'message' => '', 'drive' => []];

if (!empty($_FILES['files'])) {
    $files = $_FILES['files'];
    $uploaded = 0;

    for ($i = 0; $i < count($files['name']); $i++) {
        $tmpName = $files['tmp_name'][$i];
        $name = basename($files['name'][$i]);
        $target = $uploadDir . $name;
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowedExt = ['pdf', 'doc', 'docx'];

        if (!in_array($ext, $allowedExt)) {
            $response['message'] .= "الملف $name غير مدعوم\n";
            continue;
        }

        if (move_uploaded_file($tmpName, $target)) {
            try {
                $fileId = uploadToDrive($target, $name);
                $response['drive'][] = ['name' => $name, 'id' => $fileId];
                $uploaded++;
            } catch (Exception $e) {
                $response['drive'][] = ['name' => $name, 'error' => $e->getMessage()];
            }
        }
    }

    if ($uploaded > 0) {
        $response['success'] = true;
        $response['message'] = "$uploaded ملفات تم رفعها";
    } else {
        $response['message'] = "فشل في رفع الملفات";
    }
} else {
    $response['message'] = "لم يتم تحميل أي ملفات";
}

echo json_encode($response);
