<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FrontendLogController extends Controller
{
    /**
     * Log frontend errors to file
     */
    public function logError(Request $request)
    {
        try {
            $level = $request->input('level', 'error');
            $message = $request->input('message', 'No message');
            $url = $request->input('url', 'Unknown URL');
            $userAgent = $request->input('userAgent', 'Unknown User Agent');
            $timestamp = $request->input('timestamp', now());

            // Format log message
            $logMessage = sprintf(
                "[%s] %s\nURL: %s\nUser Agent: %s\n%s",
                strtoupper($level),
                $timestamp,
                $url,
                $userAgent,
                $message
            );

            // Log to separate frontend error log channel
            Log::channel('frontend')->error($logMessage);

            return response()->json([
                'success' => true,
                'message' => 'Error logged successfully'
            ]);
        } catch (\Exception $e) {
            // Silently fail to prevent logging errors from breaking the app
            return response()->json([
                'success' => false,
                'message' => 'Failed to log error'
            ], 500);
        }
    }
}
