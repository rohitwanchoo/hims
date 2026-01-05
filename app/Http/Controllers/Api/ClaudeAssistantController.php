<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeAssistantController extends Controller
{
    private string $apiKey;
    private string $model = 'claude-sonnet-4-20250514';
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY', ''));
    }

    /**
     * Send a message to Claude and get a response
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:10000',
            'conversation_history' => 'nullable|array',
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

            // Add the new user message
            $messages[] = [
                'role' => 'user',
                'content' => $request->message
            ];

            // System prompt for development assistant
            $systemPrompt = $this->getSystemPrompt();

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => $this->model,
                'max_tokens' => 4096,
                'system' => $systemPrompt,
                'messages' => $messages,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $assistantMessage = $data['content'][0]['text'] ?? 'No response received';

                return response()->json([
                    'success' => true,
                    'message' => $assistantMessage,
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
     * Get the system prompt for the development assistant
     */
    private function getSystemPrompt(): string
    {
        return <<<PROMPT
You are an AI Development Assistant for the HIMS (Hospital Information Management System) Laravel application. Your role is to help developers with:

1. **Code Development**: Writing new features, components, controllers, models, and migrations
2. **Bug Fixes**: Identifying and fixing issues in the codebase
3. **Architecture Guidance**: Advising on best practices, design patterns, and Laravel conventions
4. **Database Design**: Helping with migrations, relationships, and query optimization
5. **Frontend Development**: Vue.js components, routing, and state management
6. **API Development**: RESTful API design, validation, and documentation

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

When providing code:
1. Use proper Laravel/Vue conventions
2. Include necessary imports
3. Follow existing code patterns in the project
4. Provide complete, working code snippets
5. Explain what changes are needed and where

Be concise but thorough. Format code with proper syntax highlighting using markdown code blocks.
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
