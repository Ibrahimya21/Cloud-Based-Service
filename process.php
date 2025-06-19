<?php
header('Content-Type: application/json');
require 'vendor/autoload.php';

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

$uploadDir = __DIR__ . '/uploads/';
function extractTextFromElement($element)
{
    $text = '';

    if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
        $text .= $element->getText() . "\n";

    } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
        foreach ($element->getElements() as $child) {
            $text .= extractTextFromElement($child);
        }

    } elseif (method_exists($element, 'getText')) {
        // هنا نتأكد أن getText ترجع نص
        $textContent = $element->getText();
        if (is_string($textContent)) {
            $text .= $textContent . "\n";
        }
        // إذا كانت ليست نص، يمكن تجاهلها أو التعامل معها بطريقة خاصة حسب الحاجة

    } elseif (method_exists($element, 'getElements')) {
        foreach ($element->getElements() as $child) {
            $text .= extractTextFromElement($child);
        }
    }

    return $text;
}




// دالة لاسترجاع محتوى المستند (مبسطة)
function getFileContent($file)
{
    global $uploadDir;
    $path = $uploadDir . $file;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($ext === 'pdf') {
        $parser = new Smalot\PdfParser\Parser();
        try {
            $pdf = $parser->parseFile($path);
            return $pdf->getText();
        } catch (Exception $e) {
            return "";
        }
    } elseif (in_array($ext, ['doc', 'docx'])) {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
            $text = '';
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text .= extractTextFromElement($element);
                }
            }
            return $text;
        } catch (Exception $e) {
            return "";
        }
    }
    return "";
}

// دالة لاستخلاص عنوان (سطر أول)
function extractTitle($content)
{
    $lines = preg_split("/\r\n|\n|\r/", trim($content));
    return $lines[0] ?? "بدون عنوان";
}

// دالة إبراز النص داخل المحتوى
function highlightText($content, $keywords)
{
    if (!$keywords)
        return $content;
    $words = preg_split('/\s+/', trim($keywords));
    foreach ($words as $word) {
        if (!$word)
            continue;
        $content = preg_replace("/(" . preg_quote($word, '/') . ")/i", '<mark>$1</mark>', $content);
    }
    return $content;
}

$action = $_GET['action'] ?? '';
$searchText = $_GET['text'] ?? '';
$startTime = microtime(true);

$files = array_diff(scandir($uploadDir), ['.', '..']);
$documents = [];

foreach ($files as $file) {
    $content = getFileContent($file);
    $title = extractTitle($content);
    $documents[] = [
        'filename' => $file,
        'title' => $title,
        'content' => $content,
    ];
}

switch ($action) {
    case 'sort':
        usort($documents, function ($a, $b) {
            return strcmp($a['title'], $b['title']);
        });
        $resultDocs = $documents;
        break;

    case 'search':
        $resultDocs = [];
        foreach ($documents as $doc) {
            if (stripos($doc['content'], $searchText) !== false) {
                $doc['highlightedContent'] = highlightText(htmlspecialchars($doc['content']), $searchText);
                $resultDocs[] = $doc;
            }
        }
        break;

    case 'classify':
        // مثال تصنيف بسيط ثابت (يجب تعديل الشجرة حسب حاجتك)
        $classificationTree = [
            "Financial" => ["Budget", "Invoice", "Accounts", "Balance"],
            "Legal" => ["Contract", "Legislation", "Law", "Court"],
            "Technical" => ["Program", "Computer", "System", "Technology"],
        ];
        $classified = [];
        foreach ($documents as $doc) {
            $foundCategory = "غير مصنف";
            foreach ($classificationTree as $category => $keywords) {
                foreach ($keywords as $keyword) {
                    if (stripos($doc['content'], $keyword) !== false) {
                        $foundCategory = $category;
                        break 2;
                    }
                }
            }
            $doc['classification'] = $foundCategory;
            $classified[] = $doc;
        }
        $resultDocs = $classified;
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'الإجراء غير معروف']);
        exit;
}

$elapsed = microtime(true) - $startTime;
$totalSize = 0;
foreach ($documents as $doc) {
    $totalSize += filesize($uploadDir . $doc['filename']);
}

echo json_encode([
    'success' => true,
    'documents' => $resultDocs,
    'stats' => [
        'count' => count($documents),
        'totalSize' => $totalSize,
        'elapsed' => round($elapsed, 3),
    ]
]);
