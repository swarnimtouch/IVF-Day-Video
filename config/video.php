<?php

return [
    'disk' => env('VIDEO_STORAGE_DISK', 's3'),
    's3_folder' => trim(env('VIDEO_S3_FOLDER', 'ivf-day-videos'), '/'),
    'ffmpeg' => env('FFMPEG_PATH', 'ffmpeg'),
    'font' => env('VIDEO_FONT_PATH'),
];
