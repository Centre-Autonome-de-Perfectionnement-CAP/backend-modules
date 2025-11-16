<?php

namespace App\Modules\Inscription\Http\Controllers;

<<<<<<< HEAD
use App\Http\Controllers\Controller;
use App\Modules\Inscription\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function __construct(
        protected FileService $fileService
    ) {}

    public function viewLegacyFile(Request $request)
    {
        $path = urldecode($request->query('path'));
        $file = $this->fileService->getLegacyFile($path);
        
        if (!$file) {
            abort(404, 'File not found');
        }
        
        return response()->stream(function () use ($file) {
            $stream = Storage::disk('public')->readStream($file['path']);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $file['mimeType'],
=======
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController
{
    public function viewLegacyFile(Request $request)
    {
        $path = $request->query('path');
        
        if (!$path || !Storage::exists($path)) {
            abort(404, 'File not found');
        }

        $mimeType = Storage::mimeType($path);
        
        return response()->stream(function () use ($path) {
            $stream = Storage::readStream($path);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
>>>>>>> f355611 (draft)
            'Content-Disposition' => 'inline',
        ]);
    }
}
