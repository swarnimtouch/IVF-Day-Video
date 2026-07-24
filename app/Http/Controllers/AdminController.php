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
        $submissions = User::query()->where('is_admin', false)->latest()->paginate(15);
        return view('admin.dashboard', compact('submissions'));
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
