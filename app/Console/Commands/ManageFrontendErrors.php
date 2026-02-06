<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ManageFrontendErrors extends Command
{
    protected $signature = 'errors:frontend {action=view : view|clear|archive}';
    protected $description = 'Manage frontend console errors - view, clear after fixing, or archive';

    public function handle()
    {
        $action = $this->argument('action');
        $todayLog = storage_path('logs/frontend-' . date('Y-m-d') . '.log');

        switch ($action) {
            case 'view':
                $this->viewErrors($todayLog);
                break;
            case 'clear':
                $this->clearErrors($todayLog);
                break;
            case 'archive':
                $this->archiveErrors($todayLog);
                break;
            default:
                $this->error("Invalid action. Use: view, clear, or archive");
        }
    }

    private function viewErrors($logFile)
    {
        if (!File::exists($logFile)) {
            $this->info("✅ No frontend errors found today!");
            return;
        }

        $content = File::get($logFile);
        if (empty(trim($content))) {
            $this->info("✅ No frontend errors found today!");
            return;
        }

        $this->line("\n📋 Frontend Errors from Today:\n");
        $this->line(str_repeat('=', 80));

        // Parse and display errors in a readable format
        $errors = $this->parseErrors($content);

        foreach ($errors as $index => $error) {
            $this->line("\n🔴 Error #" . ($index + 1));
            $this->line("Time: " . ($error['time'] ?? 'Unknown'));
            $this->line("URL: " . ($error['url'] ?? 'Unknown'));
            $this->line("Message: " . ($error['message'] ?? 'No message'));
            $this->line(str_repeat('-', 80));
        }

        $this->line("\n📊 Total Errors: " . count($errors));
        $this->line("\n💡 Commands:");
        $this->line("  View errors:          php artisan errors:frontend view");
        $this->line("  Clear after fixing:   php artisan errors:frontend clear");
        $this->line("  Archive errors:       php artisan errors:frontend archive");
    }

    private function clearErrors($logFile)
    {
        if (!File::exists($logFile)) {
            $this->info("✅ Log file is already empty!");
            return;
        }

        File::put($logFile, '');
        $this->info("✅ Frontend error log cleared! Ready for next errors.");
    }

    private function archiveErrors($logFile)
    {
        if (!File::exists($logFile)) {
            $this->info("✅ No log file to archive!");
            return;
        }

        $content = File::get($logFile);
        if (empty(trim($content))) {
            $this->info("✅ Log file is empty, nothing to archive!");
            return;
        }

        $archiveDir = storage_path('logs/frontend-archives');
        if (!File::exists($archiveDir)) {
            File::makeDirectory($archiveDir, 0755, true);
        }

        $archiveFile = $archiveDir . '/frontend-' . date('Y-m-d_His') . '-fixed.log';
        File::copy($logFile, $archiveFile);
        File::put($logFile, '');

        $this->info("✅ Errors archived to: " . basename($archiveFile));
        $this->info("✅ Current log cleared! Ready for next errors.");
    }

    private function parseErrors($content)
    {
        $errors = [];
        $lines = explode("\n", $content);
        $currentError = null;

        foreach ($lines as $line) {
            if (preg_match('/\[([\d\-\s:]+)\]/', $line, $matches)) {
                if ($currentError) {
                    $errors[] = $currentError;
                }
                $currentError = ['time' => $matches[1]];
            } elseif ($currentError) {
                if (preg_match('/URL:\s*(.+)/', $line, $matches)) {
                    $currentError['url'] = trim($matches[1]);
                } elseif (preg_match('/User Agent:\s*(.+)/', $line, $matches)) {
                    $currentError['user_agent'] = trim($matches[1]);
                } elseif (!empty(trim($line)) && !isset($currentError['message'])) {
                    $currentError['message'] = trim($line);
                }
            }
        }

        if ($currentError) {
            $errors[] = $currentError;
        }

        return $errors;
    }
}
