/**
 * Console Error Logger
 * Captures browser console errors and sends them to server
 */

(function() {
    'use strict';

    // Store original console methods
    const originalConsoleError = console.error;
    const originalConsoleWarn = console.warn;
    const originalConsoleLog = console.log;

    // API endpoint for logging
    const LOG_ENDPOINT = '/api/log-frontend-error';

    // Helper to serialize errors properly
    function serializeError(arg) {
        if (arg instanceof Error) {
            return arg.stack || (arg.name + ': ' + arg.message);
        }
        if (typeof arg === 'object' && arg !== null) {
            try {
                // Try to get error-like properties
                if (arg.message || arg.name || arg.stack) {
                    return JSON.stringify({
                        name: arg.name,
                        message: arg.message,
                        stack: arg.stack
                    });
                }
                return JSON.stringify(arg);
            } catch (e) {
                return String(arg);
            }
        }
        return String(arg);
    }

    // Send log to server
    function sendToServer(level, args) {
        const logData = {
            level: level,
            message: Array.from(args).map(serializeError).join(' '),
            url: window.location.href,
            userAgent: navigator.userAgent,
            timestamp: new Date().toISOString()
        };

        // Send asynchronously using fetch
        fetch(LOG_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(logData),
            keepalive: true
        }).catch(function(err) {
            // Silently fail - don't log to avoid infinite loop
        });
    }

    // Override console.error
    console.error = function() {
        sendToServer('error', arguments);
        originalConsoleError.apply(console, arguments);
    };

    // Override console.warn
    console.warn = function() {
        sendToServer('warning', arguments);
        originalConsoleWarn.apply(console, arguments);
    };

    // Capture unhandled errors
    window.addEventListener('error', function(event) {
        const errorMsg = 'Unhandled Error: ' + event.message +
                        ' at ' + event.filename + ':' + event.lineno + ':' + event.colno;
        const stackTrace = event.error && event.error.stack ? '\nStack: ' + event.error.stack : '';
        sendToServer('error', [errorMsg + stackTrace]);
    });

    // Capture unhandled promise rejections
    window.addEventListener('unhandledrejection', function(event) {
        const reason = event.reason;
        let message = 'Unhandled Promise Rejection: ';

        if (reason instanceof Error) {
            message += reason.message + '\nStack: ' + reason.stack;
        } else if (typeof reason === 'object') {
            message += JSON.stringify(reason);
        } else {
            message += String(reason);
        }

        sendToServer('error', [message]);
    });

    console.log('Console logger initialized');
})();
