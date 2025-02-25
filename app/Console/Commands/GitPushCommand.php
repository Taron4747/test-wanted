<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GitPushCommand extends Command
{
    protected $signature = 'git:push';
    protected $description = 'Push result.txt to Git repository';

    public function handle()
    {
        $commands = implode(' && ', [
            'cd ' . base_path(),
            'git status',
            'git add .',
            'git commit -a -m "Add result.txt with validation errors"',
            'git push origin main'
        ]);

        exec($commands . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            Log::error("Git push failed", ['output' => implode("\n", $output)]);
        } else {
            Log::info("Git push successful", ['output' => implode("\n", $output)]);
        }

        return 0;
    }
}
