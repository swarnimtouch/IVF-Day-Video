<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function login(): View|RedirectResponse
    {
        return auth()->user()?->is_admin
            ? redirect()->route('admin.dashboard')
            : view('admin.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember')) || ! auth()->user()->is_admin) {
            Auth::logout();
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Invalid admin credentials.']);
        }

        $request->session()->regenerate();
        return redirect()->intended(route('admin.dashboard'));
    }

    public function dashboard(): View
    {
        $query = User::query()->where('is_admin', false);
        $counts = [
            'total' => (clone $query)->count(),
            'completed' => (clone $query)->where('video_status', 'completed')->count(),
            'processing' => (clone $query)->where('video_status', 'processing')->count(),
            'failed' => (clone $query)->where('video_status', 'failed')->count(),
        ];
        return view('admin.dashboard', compact('counts'));
    }

    public function submissions(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $submissions = User::query()->where('is_admin', false)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('employee_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('video_status', 'like', "%{$search}%");
                });
            })->latest()->paginate(10)->withQueryString();
        return view('admin.submissions', compact('submissions', 'search'));
    }

    public function export(): StreamedResponse
    {
        $filename = 'doctor-video-submissions-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'ID', 'Employee Code', 'Prefix', 'Doctor Name', 'City', 'Status',
                'Photo URL', 'Video URL', 'Submitted At',
            ]);

            User::query()->where('is_admin', false)->latest()->chunk(500, function ($users) use ($handle): void {
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->id,
                        $user->employee_code,
                        $user->prefix,
                        $user->name,
                        $user->city,
                        ucfirst($user->video_status),
                        $user->photo_url,
                        $user->video_url,
                        $user->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function download(User $user): StreamedResponse
    {
        abort_if($user->is_admin || ! $user->video_key, 404);
        $disk = Storage::disk(config('video.disk'));
        abort_unless($disk->exists($user->video_key), 404);
        return $disk->download($user->video_key, 'doctor-video-'.$user->employee_code.'.mp4');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
