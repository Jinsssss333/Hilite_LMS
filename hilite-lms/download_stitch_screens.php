<?php

$jsonFile = 'C:/Users/jinsj/.gemini/antigravity/brain/b26622fe-1122-41ef-885d-6ea7da54db58/.system_generated/steps/509/output.txt';
$data = json_decode(file_get_contents($jsonFile), true);

$outputDir = __DIR__ . '/frontend_images/stitch_html';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

foreach ($data['screens'] as $screen) {
    if (isset($screen['htmlCode']['downloadUrl'])) {
        $title = preg_replace('/[^A-Za-z0-9_\-]/', '_', $screen['title']);
        $url = $screen['htmlCode']['downloadUrl'];
        echo "Downloading: {$screen['title']}...\n";
        
        $html = file_get_contents($url);
        if ($html !== false) {
            file_put_contents("$outputDir/{$title}.html", $html);
            echo "Saved to {$title}.html\n";
        } else {
            echo "Failed to download {$title}\n";
        }
    }
}
echo "Done!\n";
