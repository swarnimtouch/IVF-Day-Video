<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process;
use Throwable;

class VideoController extends Controller
{
    public function create(): View
    {
        $completedUser = session('completed_user_id')
            ? User::find(session('completed_user_id'))
            : null;

        return view('welcome', compact('completedUser'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9]+$/'],
            'prefix' => ['required', 'in:Dr.,Prof.,Mr.,Ms.,Mrs.'],
            'doctor_name' => ['required', 'string', 'max:80', 'regex:/^[\pL\pM .\'-]+$/u'],
            'city' => ['nullable', 'string', 'max:80', 'regex:/^[\pL\pM .\'-]+$/u'],
            'photo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
        ], [
            'employee_code.regex' => 'Employee code may contain letters and numbers only.',
            'doctor_name.regex' => 'Doctor name contains invalid characters.',
        ]);

        $sourceVideo = public_path('assets/base-video.mp4');
        abort_unless(File::exists($sourceVideo), 500, 'The base video is not configured.');

        $jobId = now()->format('Ymd-His').'-'.Str::lower(Str::random(6));
        $workDir = storage_path("app/video-jobs/{$jobId}");
        File::ensureDirectoryExists($workDir);
        $photoPath = $request->file('photo')->move($workDir, 'doctor-photo.png')->getPathname();
        $outputPath = $workDir.DIRECTORY_SEPARATOR.'doctor-video.mp4';
        $doctorLabel = trim($data['prefix'].' '.$data['doctor_name']);

        $user = User::create([
            'name' => $data['doctor_name'],
            'employee_code' => strtoupper($data['employee_code']),
            'prefix' => $data['prefix'],
            'city' => $data['city'] ?? null,
            'download_token' => hash('sha256', Str::random(64)),
            'video_status' => 'processing',
        ]);

        try {
            $this->generateVideo($sourceVideo, $photoPath, $outputPath, $doctorLabel);
        } catch (Throwable $exception) {
            report($exception);
            $user->update(['video_status' => 'failed']);
            File::deleteDirectory($workDir);
            return back()->withInput()->withErrors([
                'video' => 'FFmpeg could not generate the video. Please check the configured FFmpeg path and font.',
            ]);
        }

        try {
            $disk = Storage::disk(config('video.disk'));
            $folder = config('video.s3_folder').'/'.now()->format('Y/m').'/'.$user->id;
            $photoKey = $folder.'/doctor-photo.png';
            $videoKey = $folder.'/doctor-video.mp4';

            $disk->put($photoKey, fopen($photoPath, 'rb'));
            $disk->put($videoKey, fopen($outputPath, 'rb'));

            $user->update([
                'photo_key' => $photoKey,
                'photo_url' => $disk->url($photoKey),
                'video_key' => $videoKey,
                'video_url' => $disk->url($videoKey),
                'video_status' => 'completed',
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $user->update(['video_status' => 'failed']);
            File::deleteDirectory($workDir);
            $networkFailure = str_contains($exception->getMessage(), 'cURL error 7')
                || str_contains($exception->getMessage(), 'Couldn\'t connect to server');
            return back()->withInput()->withErrors([
                'video' => $networkFailure
                    ? 'The video was generated, but this server could not connect to AWS S3 on port 443. Please check outbound network/firewall access.'
                    : 'The video was generated, but S3 upload failed. Please check bucket credentials, region and permissions.',
            ]);
        }

        File::deleteDirectory($workDir);
        return redirect()->route('video.form')->with('completed_user_id', $user->id)->withFragment('result');
    }

    public function download(User $user, string $token): StreamedResponse
    {
        abort_unless(hash_equals((string) $user->download_token, $token) && $user->video_key, 404);
        $disk = Storage::disk(config('video.disk'));
        abort_unless($disk->exists($user->video_key), 404);
        return $disk->download($user->video_key, 'doctor-video-'.$user->employee_code.'.mp4');
    }

    private function generateVideo(string $source, string $photo, string $output, string $label): void
    {
        $font = $this->fontPath();
        $filter = "[1:v]scale=300:300[photo];".
            "[0:v][photo]overlay=x=(W-w)/2:y=(H-h)/2-75:enable='gte(t,28)'[withphoto];".
            "[withphoto]drawtext=fontfile='{$font}':text='{$this->escapeDrawText($label)}':fontcolor=white:fontsize=54:".
            "borderw=3:bordercolor=black@0.35:shadowcolor=black@0.55:shadowx=3:shadowy=3:".
            "x=(w-text_w)/2:y=(h/2)+190:enable='gte(t,28)'[outv]";

        $process = new Process([
            config('video.ffmpeg'), '-y', '-i', $source, '-i', $photo,
            '-filter_complex', $filter, '-map', '[outv]', '-map', '0:a?',
            '-c:v', 'libx264', '-preset', 'ultrafast', '-crf', '23', '-threads', '0', '-pix_fmt', 'yuv420p',
            '-c:a', 'copy', '-movflags', '+faststart', $output,
        ]);
        $process->setTimeout(300);
        $process->mustRun();
    }

    private function fontPath(): string
    {
        $candidates = array_filter([
            config('video.font'),
            'C:/Windows/Fonts/arialbd.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf',
        ]);
        foreach ($candidates as $font) {
            if (File::exists($font)) {
                $font = str_replace('\\', '/', $font);
                return preg_replace('/^([A-Za-z]):\//', '$1\\:/', $font);
            }
        }
        throw new \RuntimeException('No compatible video font was found. Set VIDEO_FONT_PATH.');
    }

    private function escapeDrawText(string $text): string
    {
        return str_replace(['\\', ':', "'", '%', '[', ']'], ['\\\\', '\\:', "\\'", '\\%', '\\[', '\\]'], $text);
    }
}
