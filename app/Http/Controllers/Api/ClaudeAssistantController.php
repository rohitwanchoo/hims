<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ClaudeAssistantController extends Controller
{
    private string $apiKey;
    private string $model = 'claude-sonnet-4-20250514';
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $basePath;

    // Allowed directories for file operations (security)
    private array $allowedPaths = [
        'app/',
        'config/',
        'database/',
        'resources/',
        'routes/',
        'tests/',
    ];

    // Forbidden file patterns
    private array $forbiddenPatterns = [
        '.env',
        '.git/',
        'vendor/',
        'node_modules/',
        'storage/logs/',
        '.php_cs',
        'auth.json',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY', ''));
        $this->basePath = base_path();
    }

    /**
     * Send a message to Claude and get a response with code change detection
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:10000',
            'conversation_history' => 'nullable|array',
            'include_file_context' => 'nullable|boolean',
            'files_to_read' => 'nullable|array',
        ]);

        if (empty($this->apiKey)) {
            return response()->json([
                'success' => false,
                'error' => 'Claude API key is not configured. Please set ANTHROPIC_API_KEY in your .env file.'
            ], 500);
        }

        try {
            // Build messages array with conversation history
            $messages = [];

            if ($request->conversation_history) {
                foreach ($request->conversation_history as $msg) {
                    $messages[] = [
                        'role' => $msg['role'],
                        'content' => $msg['content']
                    ];
                }
            }

            // If files are requested to be read, include their content
            $fileContext = '';
            if ($request->files_to_read && is_array($request->files_to_read)) {
                foreach ($request->files_to_read as $filePath) {
                    $content = $this->readFile($filePath);
                    if ($content !== null) {
                        $fileContext .= "\n\n--- File: {$filePath} ---\n```\n{$content}\n```\n";
                    }
                }
            }

            $userMessage = $request->message;
            if ($fileContext) {
                $userMessage = "Context files:\n{$fileContext}\n\nUser request: {$request->message}";
            }

            // Add the new user message
            $messages[] = [
                'role' => 'user',
                'content' => $userMessage
            ];

            // System prompt for development assistant with code change capabilities
            $systemPrompt = $this->getSystemPrompt();

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => $this->model,
                'max_tokens' => 8192,
                'system' => $systemPrompt,
                'messages' => $messages,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $assistantMessage = $data['content'][0]['text'] ?? 'No response received';

                // Parse the response for code changes
                $codeChanges = $this->parseCodeChanges($assistantMessage);

                return response()->json([
                    'success' => true,
                    'message' => $assistantMessage,
                    'code_changes' => $codeChanges,
                    'has_changes' => count($codeChanges) > 0,
                    'usage' => $data['usage'] ?? null,
                ]);
            } else {
                Log::error('Claude API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Failed to get response from Claude: ' . ($response->json()['error']['message'] ?? 'Unknown error')
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Claude Assistant Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Read a file's content
     */
    public function readFile(string $filePath): ?string
    {
        // Security check
        if (!$this->isPathAllowed($filePath)) {
            return null;
        }

        $fullPath = $this->basePath . '/' . ltrim($filePath, '/');

        if (!File::exists($fullPath)) {
            return null;
        }

        try {
            return File::get($fullPath);
        } catch (\Exception $e) {
            Log::error('Failed to read file', ['path' => $filePath, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * API endpoint to read a file
     */
    public function getFile(Request $request)
    {
        $request->validate([
            'path' => 'required|string|max:500',
        ]);

        $filePath = $request->input('path');

        if (!$this->isPathAllowed($filePath)) {
            return response()->json([
                'success' => false,
                'error' => 'Access to this file is not allowed'
            ], 403);
        }

        $content = $this->readFile($filePath);

        if ($content === null) {
            return response()->json([
                'success' => false,
                'error' => 'File not found or cannot be read'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'path' => $filePath,
            'content' => $content,
        ]);
    }

    /**
     * Apply code changes to a file
     */
    public function applyChanges(Request $request)
    {
        $request->validate([
            'changes' => 'required|array',
            'changes.*.path' => 'required|string|max:500',
            'changes.*.content' => 'required|string',
            'changes.*.action' => 'required|in:create,update,delete',
            'commit_message' => 'nullable|string|max:500',
        ]);

        $appliedChanges = [];
        $errors = [];

        foreach ($request->changes as $change) {
            $filePath = $change['path'];
            $content = $change['content'];
            $action = $change['action'];

            // Security check
            if (!$this->isPathAllowed($filePath)) {
                $errors[] = [
                    'path' => $filePath,
                    'error' => 'Access to this file is not allowed'
                ];
                continue;
            }

            $fullPath = $this->basePath . '/' . ltrim($filePath, '/');

            try {
                if ($action === 'delete') {
                    if (File::exists($fullPath)) {
                        File::delete($fullPath);
                        $appliedChanges[] = ['path' => $filePath, 'action' => 'deleted'];
                    }
                } else {
                    // Create directory if it doesn't exist
                    $directory = dirname($fullPath);
                    if (!File::isDirectory($directory)) {
                        File::makeDirectory($directory, 0755, true);
                    }

                    // Write the file
                    File::put($fullPath, $content);

                    // Fix permissions
                    chmod($fullPath, 0644);

                    $appliedChanges[] = [
                        'path' => $filePath,
                        'action' => $action === 'create' ? 'created' : 'updated'
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'path' => $filePath,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Git commit if requested and changes were applied
        $gitResult = null;
        if ($request->commit_message && count($appliedChanges) > 0) {
            $gitResult = $this->gitCommit($appliedChanges, $request->commit_message);
        }

        return response()->json([
            'success' => count($errors) === 0,
            'applied' => $appliedChanges,
            'errors' => $errors,
            'git' => $gitResult,
        ]);
    }

    /**
     * Get file diff (preview before applying)
     */
    public function previewDiff(Request $request)
    {
        $request->validate([
            'path' => 'required|string|max:500',
            'new_content' => 'required|string',
        ]);

        $filePath = $request->input('path');
        $newContent = $request->input('new_content');

        if (!$this->isPathAllowed($filePath)) {
            return response()->json([
                'success' => false,
                'error' => 'Access to this file is not allowed'
            ], 403);
        }

        $currentContent = $this->readFile($filePath) ?? '';

        // Generate diff
        $diff = $this->generateDiff($currentContent, $newContent, $filePath);

        return response()->json([
            'success' => true,
            'path' => $filePath,
            'is_new_file' => empty($currentContent),
            'current_content' => $currentContent,
            'new_content' => $newContent,
            'diff' => $diff,
        ]);
    }

    /**
     * List files in a directory
     */
    public function listFiles(Request $request)
    {
        $request->validate([
            'path' => 'nullable|string|max:500',
            'pattern' => 'nullable|string|max:100',
        ]);

        $path = $request->input('path', '');
        $pattern = $request->input('pattern', '*');

        if ($path && !$this->isPathAllowed($path)) {
            return response()->json([
                'success' => false,
                'error' => 'Access to this path is not allowed'
            ], 403);
        }

        $fullPath = $this->basePath . '/' . ltrim($path, '/');

        if (!File::isDirectory($fullPath)) {
            return response()->json([
                'success' => false,
                'error' => 'Directory not found'
            ], 404);
        }

        try {
            $files = [];
            $items = File::glob($fullPath . '/' . $pattern);

            foreach ($items as $item) {
                $relativePath = str_replace($this->basePath . '/', '', $item);

                // Skip forbidden paths
                $skip = false;
                foreach ($this->forbiddenPatterns as $forbidden) {
                    if (Str::contains($relativePath, $forbidden)) {
                        $skip = true;
                        break;
                    }
                }

                if (!$skip) {
                    $files[] = [
                        'path' => $relativePath,
                        'name' => basename($item),
                        'is_directory' => File::isDirectory($item),
                        'size' => File::isDirectory($item) ? null : File::size($item),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'path' => $path,
                'files' => $files,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search for files
     */
    public function searchFiles(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'type' => 'nullable|in:name,content',
        ]);

        $query = $request->input('query');
        $type = $request->input('type', 'name');

        $results = [];

        foreach ($this->allowedPaths as $allowedPath) {
            $fullPath = $this->basePath . '/' . $allowedPath;

            if (!File::isDirectory($fullPath)) {
                continue;
            }

            $files = File::allFiles($fullPath);

            foreach ($files as $file) {
                $relativePath = str_replace($this->basePath . '/', '', $file->getPathname());

                // Skip forbidden paths
                $skip = false;
                foreach ($this->forbiddenPatterns as $forbidden) {
                    if (Str::contains($relativePath, $forbidden)) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip) continue;

                if ($type === 'name') {
                    if (Str::contains(strtolower($file->getFilename()), strtolower($query))) {
                        $results[] = [
                            'path' => $relativePath,
                            'name' => $file->getFilename(),
                            'match_type' => 'filename',
                        ];
                    }
                } else {
                    // Content search (limit to reasonable file sizes)
                    if ($file->getSize() < 500000) { // 500KB limit
                        try {
                            $content = File::get($file->getPathname());
                            if (Str::contains(strtolower($content), strtolower($query))) {
                                $results[] = [
                                    'path' => $relativePath,
                                    'name' => $file->getFilename(),
                                    'match_type' => 'content',
                                ];
                            }
                        } catch (\Exception $e) {
                            // Skip files that can't be read
                        }
                    }
                }

                // Limit results
                if (count($results) >= 50) {
                    break 2;
                }
            }
        }

        return response()->json([
            'success' => true,
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * Git commit changes
     */
    private function gitCommit(array $changes, string $message): array
    {
        try {
            $filePaths = array_map(fn($c) => $c['path'], $changes);

            // Add files to git
            foreach ($filePaths as $path) {
                exec("cd {$this->basePath} && git add " . escapeshellarg($path) . " 2>&1", $output, $returnCode);
            }

            // Create commit
            $fullMessage = $message . "\n\n🤖 Generated with Claude AI Assistant\n\nFiles changed:\n" . implode("\n", array_map(fn($c) => "- {$c['path']} ({$c['action']})", $changes));

            exec("cd {$this->basePath} && git commit -m " . escapeshellarg($fullMessage) . " 2>&1", $output, $returnCode);

            return [
                'success' => $returnCode === 0,
                'message' => implode("\n", $output),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if a path is allowed for file operations
     */
    private function isPathAllowed(string $path): bool
    {
        $path = ltrim($path, '/');

        // Check forbidden patterns
        foreach ($this->forbiddenPatterns as $pattern) {
            if (Str::contains($path, $pattern)) {
                return false;
            }
        }

        // Check if path starts with allowed directories
        foreach ($this->allowedPaths as $allowed) {
            if (Str::startsWith($path, $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a unified diff between two strings
     */
    private function generateDiff(string $old, string $new, string $filename): string
    {
        $oldLines = explode("\n", $old);
        $newLines = explode("\n", $new);

        $diff = "--- a/{$filename}\n+++ b/{$filename}\n";

        // Simple line-by-line diff
        $maxLines = max(count($oldLines), count($newLines));
        $changes = [];
        $lineNum = 1;

        for ($i = 0; $i < $maxLines; $i++) {
            $oldLine = $oldLines[$i] ?? null;
            $newLine = $newLines[$i] ?? null;

            if ($oldLine === $newLine) {
                $changes[] = " {$oldLine}";
            } else {
                if ($oldLine !== null) {
                    $changes[] = "-{$oldLine}";
                }
                if ($newLine !== null) {
                    $changes[] = "+{$newLine}";
                }
            }
            $lineNum++;
        }

        $diff .= "@@ -1," . count($oldLines) . " +1," . count($newLines) . " @@\n";
        $diff .= implode("\n", $changes);

        return $diff;
    }

    /**
     * Parse code changes from assistant response
     */
    private function parseCodeChanges(string $response): array
    {
        $changes = [];

        // Pattern to match code blocks with file paths
        // Matches patterns like:
        // ```php:app/Models/Patient.php
        // ```javascript:resources/js/components/Test.vue
        // Or FILE: path/to/file followed by code block
        $patterns = [
            // Pattern 1: ```lang:filepath
            '/```(\w+):([^\n]+)\n(.*?)```/s',
            // Pattern 2: **File: filepath** or FILE: filepath followed by code
            '/(?:\*\*File:\s*|FILE:\s*)([^\n\*]+)\*?\*?\n```(\w*)\n(.*?)```/s',
            // Pattern 3: Create/Update file: filepath
            '/(?:Create|Update|Edit)\s+(?:file:?\s*)?[`\'"]?([^\n`\'"]+)[`\'"]?\s*:?\n```(\w*)\n(.*?)```/si',
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $response, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                if (count($match) >= 4) {
                    // Determine path and content based on pattern
                    if (preg_match('/^\w+$/', $match[1]) && strlen($match[1]) < 20) {
                        // Pattern 1: lang:filepath format
                        $lang = $match[1];
                        $path = trim($match[2]);
                        $content = $match[3];
                    } else {
                        // Pattern 2 & 3: filepath then lang
                        $path = trim($match[1]);
                        $lang = $match[2] ?? '';
                        $content = $match[3];
                    }

                    // Clean the path
                    $path = preg_replace('/^[`\'"\s]+|[`\'"\s]+$/', '', $path);
                    $path = ltrim($path, '/');

                    // Skip if path doesn't look valid
                    if (empty($path) || !preg_match('/\.\w+$/', $path)) {
                        continue;
                    }

                    // Check if file exists to determine action
                    $fullPath = $this->basePath . '/' . $path;
                    $action = File::exists($fullPath) ? 'update' : 'create';

                    $changes[] = [
                        'path' => $path,
                        'content' => trim($content),
                        'language' => $lang,
                        'action' => $action,
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Get the system prompt for the development assistant
     */
    private function getSystemPrompt(): string
    {
        return <<<PROMPT
You are an AI Development Assistant for the HIMS (Hospital Information Management System) Laravel application. You can help developers write code AND apply changes directly to the codebase.

## IMPORTANT: Code Change Format

When suggesting code changes that should be applied to files, ALWAYS use this format:

```language:path/to/file
// code content here
```

For example:
```php:app/Models/Patient.php
<?php

namespace App\Models;

class Patient extends Model
{
    // ... code
}
```

```vue:resources/js/views/patients/PatientList.vue
<template>
    <!-- template code -->
</template>
```

This format allows the system to detect and apply your code changes automatically.

## Project Context:
- **Framework**: Laravel 11 with Vue.js 3 (Composition API)
- **Database**: MySQL with multi-hospital support (hospital_id scoping)
- **Authentication**: Laravel Sanctum for API authentication
- **Styling**: Bootstrap 5 with Bootstrap Icons
- **State Management**: Pinia stores
- **Key Features**: Patient management, OPD/IPD, Appointments, Laboratory, Radiology, Billing, Pharmacy, Inventory

## Code Structure:
- Controllers: `app/Http/Controllers/Api/`
- Models: `app/Models/`
- Migrations: `database/migrations/`
- Vue Components: `resources/js/views/`
- Router: `resources/js/router/index.js`
- API Routes: `routes/api.php`

## Important Patterns:
- Models use `BelongsToHospital` trait for multi-hospital filtering
- API responses use consistent JSON format
- Vue components use Composition API with `<script setup>`
- Forms use axios for API calls with proper error handling

## Guidelines:
1. Always use the file path format: ```language:path/to/file
2. Provide complete, working code (not snippets with "..." placeholders)
3. Follow existing code patterns in the project
4. Include necessary imports
5. For new files, provide the complete file content
6. For updates, provide the complete updated file

When the user approves changes, they will be applied directly to the codebase.
PROMPT;
    }

    /**
     * Get available models
     */
    public function models()
    {
        return response()->json([
            'models' => [
                ['id' => 'claude-sonnet-4-20250514', 'name' => 'Claude Sonnet 4'],
                ['id' => 'claude-3-5-sonnet-20241022', 'name' => 'Claude 3.5 Sonnet'],
                ['id' => 'claude-3-haiku-20240307', 'name' => 'Claude 3 Haiku (Fast)'],
            ]
        ]);
    }
}
