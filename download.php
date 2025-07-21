<?php
// download.php: YouTube to MP3 downloader backend
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['youtube_url'])) {
    $youtube_url = escapeshellarg($_POST['youtube_url']);
    $output_dir = sys_get_temp_dir();
    $output_file = tempnam($output_dir, 'ytmp3_') . '.mp3';
    $yt_dlp_path = __DIR__ . '/yt-dlp';
    if (!file_exists($yt_dlp_path) || !is_executable($yt_dlp_path)) {
        echo '<div class="alert alert-danger">yt-dlp script is missing or not executable.</div>';
        exit;
    }
    $cmd = "$yt_dlp_path -x --audio-format mp3 --audio-quality 0 --output '{$output_dir}/%(title)s.%(ext)s' $youtube_url 2>&1";
    exec($cmd, $output, $return_var);
    if ($return_var === 0) {
        // Find the generated MP3 file
        $files = glob("$output_dir/*.mp3");
        $latest_file = array_reduce($files, function ($a, $b) {
            return filemtime($a) > filemtime($b) ? $a : $b;
        });
        if ($latest_file && file_exists($latest_file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: audio/mpeg');
            header('Content-Disposition: attachment; filename="' . basename($latest_file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($latest_file));
            readfile($latest_file);
            unlink($latest_file); // Clean up
            exit;
        } else {
            echo '<div class="alert alert-danger">Failed to find the MP3 file.</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Failed to download or convert the video. Output:<br><pre>' . htmlspecialchars(implode("\n", $output)) . '</pre></div>';
    }
} else {
    echo '<div class="alert alert-warning">Invalid request. Please provide a valid YouTube URL.</div>';
}

?>