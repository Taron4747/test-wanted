<?
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;
use App\Models\Row;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProcessExcelFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $redisKey;

    public function __construct($filePath, $redisKey)
    {
        $this->filePath = $filePath;
        $this->redisKey = $redisKey;
    }

    public function handle()
    {
        $rows = Excel::toArray([], Storage::path($this->filePath))[0];
        array_shift($rows); 

        $errors = [];
        $processedRows = 0;
        foreach (array_chunk($rows, 1000) as $chunkIndex => $chunk) {
            $chunkedData =[];
            foreach ($chunk as $index => $row) {
                $lineNumber = $chunkIndex * 1000 + $index + 2; 
                [$id, $name, $date] = $row;

                $errorMessages = [];

                if (!is_numeric($id) || Row::where('id', $id)->exists()) {
                    $errorMessages[] = "Неверный ID или дубликат";
                }
                if (!preg_match('/^[A-Za-z ]+$/', $name)) {
                    $errorMessages[] = "Некорректное имя";
                }
                if (!Carbon::createFromFormat('d.m.Y', $date)) {
                    $errorMessages[] = "Неверный формат даты";
                }

                if (!empty($errorMessages)) {
                    $errors[] = "$lineNumber - " . implode(", ", $errorMessages);
                    continue;
                }
                $chunkedData []=[
                    'id' => $id, 
                    'name' => $name, 
                    'date' => Carbon::createFromFormat('d.m.Y', $date)->toDateString()

                ];
                $processedRows++;
            }
            if (count($chunkedData )) {
                Row::insert($chunkedData );
                
            }
            Redis::set($this->redisKey, $processedRows);
        }
        if (count($errors)) {
            Storage::put('result.txt', implode("\n", $errors));
        

            $content = "";
            foreach ($errors as $error) {
                $content .= $error . "\n";
            }
            $directoryPath = base_path('error-files');
            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0755, true);
            }
            $filePath = $directoryPath . '/result'. $this->redisKey .'.txt';
            File::put($filePath, $content);
            $commands = implode(' && ', [
                'cd ' . base_path(),
                'git status',
                'git add .',
                'git commit -a -m "Add result.txt with validation errors"',
                'git push origin main'
            ]);
            
            exec($commands . ' 2>&1', $output, $returnVar);
            
            if ($returnVar !== 0) {
                Log::error("Git command failed", ['output' => implode("\n", $output)]);
            }
        }
      
    }
}