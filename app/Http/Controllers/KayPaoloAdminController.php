<?php

namespace App\Http\Controllers;

use App\Services\KayPaoloContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KayPaoloAdminController extends Controller
{
    public function edit(KayPaoloContent $content): View
    {
        $this->authorizeAdmin();

        return view('pages.admin', [
            'content' => $content->all(),
        ]);
    }

    public function update(Request $request, KayPaoloContent $content): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'meta_description' => ['required', 'string', 'max:300'],
            'who_headline' => ['required', 'string', 'max:160'],
            'who_body' => ['required', 'string', 'max:1200'],
            'process_step_1_title' => ['required', 'string', 'max:120'],
            'process_step_1_body' => ['required', 'string', 'max:500'],
            'who_image_primary' => ['nullable', 'image', 'max:4096'],
            'who_image_secondary' => ['nullable', 'image', 'max:4096'],
        ]);

        foreach (['who_image_primary', 'who_image_secondary'] as $field) {
            if (!$request->hasFile($field)) {
                unset($validated[$field]);
                continue;
            }

            $validated[$field] = $this->storeImage($request->file($field), $field);
        }

        $content->update($validated);

        return redirect()
            ->route('admin')
            ->with('status', 'Kay Paolo content updated.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless((int) session('zion.user.role_id') === 1, 403);
    }

    private function storeImage(\Illuminate\Http\UploadedFile $file, string $field): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $filename = Str::slug($field).'-'.now()->format('YmdHis').'-'.Str::random(8).'.'.$extension;
        $directory = public_path('kay-paolo/assets/images');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $filename);

        return 'kay-paolo/assets/images/'.$filename;
    }
}
