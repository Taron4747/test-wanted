<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessExcelFile;
use Illuminate\Support\Facades\Redis;

class ExcelUploadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.basic');
    }

    public function showUploadForm()
    {
        return view('upload');
    }

   

    public function handleUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx',
        ]);

        $filePath = $request->file('file')->store('uploads');
        $redisKey = 'import_progress_' . uniqid();
        Redis::set($redisKey, 0);
        ProcessExcelFile::dispatch($filePath, $redisKey);

        return response()->json(['message' => 'Файл загружен и отправлен в очередь']);
    }
}
