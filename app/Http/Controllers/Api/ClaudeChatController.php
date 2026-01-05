<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ClaudeChatController extends Controller
{
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $model = 'claude-sonnet-4-20250514';

    /**
     * Check if user is super admin
     */
    private function checkSuperAdmin()
    {
        $user = Auth::user();
        if (!$user || !$user->is_super_admin) {
            abort(403, 'Access denied. Super admin privileges required.');
        }
        return $user;
    }

    /**
     * Send message to Claude
     */
    public function sendMessage(Request $request)
    {
        $this->checkSuperAdmin();

        $request->validate([
            'message' => 'required|string|max:10000',
            'conversation_id' => 'nullable|string',
        ]);

        $apiKey = config('services.anthropic.api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Anthropic API key not configured. Add ANTHROPIC_API_KEY to your .env file.',
            ], 500);
        }

        // Get or create conversation history
        $conversationId = $request->conversation_id ?? uniqid('conv_');
        $cacheKey = 'claude_chat_' . Auth::id() . '_' . $conversationId;
        $conversationHistory = Cache::get($cacheKey, []);

        // Add user message to history
        $conversationHistory[] = [
            'role' => 'user',
            'content' => $request->message,
        ];

        // System prompt for the assistant
        $systemPrompt = $this->getSystemPrompt();

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => $this->model,
                'max_tokens' => 4096,
                'system' => $systemPrompt,
                'messages' => $conversationHistory,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $assistantMessage = $data['content'][0]['text'] ?? 'No response received';

                // Add assistant response to history
                $conversationHistory[] = [
                    'role' => 'assistant',
                    'content' => $assistantMessage,
                ];

                // Save conversation (keep last 20 messages to manage context window)
                if (count($conversationHistory) > 40) {
                    $conversationHistory = array_slice($conversationHistory, -40);
                }
                Cache::put($cacheKey, $conversationHistory, now()->addHours(24));

                return response()->json([
                    'success' => true,
                    'message' => $assistantMessage,
                    'conversation_id' => $conversationId,
                    'usage' => $data['usage'] ?? null,
                ]);
            } else {
                Log::error('Claude API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'API Error: ' . ($response->json()['error']['message'] ?? $response->body()),
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Claude Chat Exception', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get conversation history
     */
    public function getHistory(Request $request)
    {
        $this->checkSuperAdmin();

        $conversationId = $request->query('conversation_id');
        if (!$conversationId) {
            return response()->json([
                'success' => true,
                'messages' => [],
                'conversation_id' => null,
            ]);
        }

        $cacheKey = 'claude_chat_' . Auth::id() . '_' . $conversationId;
        $history = Cache::get($cacheKey, []);

        return response()->json([
            'success' => true,
            'messages' => $history,
            'conversation_id' => $conversationId,
        ]);
    }

    /**
     * Clear conversation
     */
    public function clearConversation(Request $request)
    {
        $this->checkSuperAdmin();

        $conversationId = $request->conversation_id;
        if ($conversationId) {
            $cacheKey = 'claude_chat_' . Auth::id() . '_' . $conversationId;
            Cache::forget($cacheKey);
        }

        return response()->json([
            'success' => true,
            'message' => 'Conversation cleared',
        ]);
    }

    /**
     * List all conversations
     */
    public function listConversations()
    {
        $this->checkSuperAdmin();

        // For simplicity, we'll just return a new conversation option
        // In production, you'd want to store conversation metadata in a database
        return response()->json([
            'success' => true,
            'conversations' => [],
        ]);
    }

    /**
     * Get system prompt for Claude
     */
    private function getSystemPrompt(): string
    {
        $projectPath = base_path();

        return <<<PROMPT
You are an expert Laravel and Vue.js developer assistant integrated into the HIMS (Hospital Information Management System) admin panel.

## Your Role
You help the system administrator with:
1. Understanding the codebase structure and functionality
2. Debugging issues and errors
3. Suggesting code improvements and new features
4. Providing implementation guidance for new modules
5. Answering questions about Laravel, Vue.js, and web development

## Project Context
- **Framework**: Laravel 11 with Vue.js 3
- **Database**: MySQL
- **Authentication**: Laravel Sanctum
- **Frontend Build**: Vite
- **Project Path**: {$projectPath}

## HIMS Modules
The system includes these main modules:
- **Patient Management**: Patient registration, search, history
- **OPD (Outpatient)**: Appointments, consultations, prescriptions
- **IPD (Inpatient)**: Admissions, bed management, discharge
- **Billing**: Service charges, payments, receipts
- **Pharmacy**: Drug inventory, dispensing
- **Laboratory**: Test orders, results
- **Masters**: Departments, doctors, wards, beds, services

## Key Directories
- `/app/Http/Controllers/Api/` - API Controllers
- `/app/Models/` - Eloquent Models
- `/resources/js/views/` - Vue.js Components
- `/resources/js/router/` - Vue Router
- `/database/migrations/` - Database Migrations
- `/routes/api.php` - API Routes

## Response Guidelines
1. Be concise but thorough
2. Provide code examples when helpful
3. Explain the reasoning behind suggestions
4. Consider security best practices
5. Follow Laravel and Vue.js conventions
6. When suggesting file changes, specify the exact file path

## Important Notes
- Always consider the multi-hospital tenant architecture (hospital_id filtering)
- The system uses Sanctum for API authentication
- Frontend components use Bootstrap 5 for styling
- Always validate user input and sanitize outputs

You are here to help the admin efficiently manage and improve the HIMS system. Be helpful, accurate, and practical in your responses.
PROMPT;
    }
}
