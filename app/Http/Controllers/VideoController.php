<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\Process\Process;
use Throwable;

class VideoController extends Controller
{
    public function create(): View
    {
        return view('welcome');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-_\/]+$/'],
            'prefix' => ['required', 'in:Dr.,Prof.,Mr.,Ms.,Mrs.'],
            'doctor_name' => ['required', 'string', 'max:80'],
            'city' => ['required', 'string', 'max:80'],
            'photo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
        ]);

        $sourceVideo = public_path('assets/base-video.mp4');
        abort_unless(File::exists($sourceVideo), 500, 'The base video is not configured.');

        $jobId = now()->format('Ymd-His').'-'.Str::lower(Str::random(6));
        $workDir = storage_path("app/video-jobs/{$jobId}");
        $outputDir = public_path('generated');
        File::ensureDirectoryExists($workDir);
        File::ensureDirectoryExists($outputDir);

        $photoPath = $request->file('photo')->move($workDir, 'doctor-photo.png')->getPathname();
        $outputName = 'doctor-video-'.$jobId.'.mp4';
        $outputPath = $outputDir.DIRECTORY_SEPARATOR.$outputName;
        $doctorLabel = trim($data['prefix'].' '.$data['doctor_name']);
        $safeLabel = $this->escapeDrawText($doctorLabel);
        $font = str_replace('\\', '/', env('VIDEO_FONT_PATH', 'C:/Windows/Fonts/arialbd.ttf'));

        $filter = "[1:v]scale=220:220[photo];".
            "[0:v][photo]overlay=x=(W-w)/2-210:y=(H-h)/2:enable='gte(t,28)'[withphoto];".
            "[withphoto]drawtext=fontfile='{$font}':text='{$safeLabel}':fontcolor=white:fontsize=54:".
            "borderw=3:bordercolor=black@0.35:shadowcolor=black@0.55:shadowx=3:shadowy=3:".
            "x=(w/2)+35:y=(h-text_h)/2:enable='gte(t,28)'[outv]";

        $process = new Process([
            env('FFMPEG_PATH', 'ffmpeg'), '-y', '-i', $sourceVideo, '-i', $photoPath,
            '-filter_complex', $filter, '-map', '[outv]', '-map', '0:a?',
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '20',
            '-c:a', 'copy', '-movflags', '+faststart', $outputPath,
        ]);
        $process->setTimeout(300);

        try {
            $process->mustRun();
        } catch (Throwable $exception) {
            report($exception);
            File::deleteDirectory($workDir);
            return back()->withInput()->withErrors(['video' => 'The video could not be generated. Please try again.']);
        }

        File::deleteDirectory($workDir);

        return redirect()->route('video.form')->with([
            'video_url' => asset('generated/'.$outputName).'#t=27.5',
            'doctor_label' => $doctorLabel.' · '.$data['city'],
        ])->withFragment('result');
    }

    private function escapeDrawText(string $text): string
    {
        return str_replace(
            ['\\', ':', "'", '%', '[', ']'],
            ['\\\\', '\\:', "\\'", '\\%', '\\[', '\\]'],
            $text,
        );
    }
}
