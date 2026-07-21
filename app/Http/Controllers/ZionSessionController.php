<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ZionSessionController extends Controller
{
    public function showLogin(): View
    {
        return view('pages.login');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('zion');

        return redirect()->route('home');
    }

    public function dashboard(): View
    {
        return view('pages.dashboard');
    }
}
