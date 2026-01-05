<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentHospital
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Super admins can switch hospitals or see all
            if ($user->isSuperAdmin()) {
                // Check if a specific hospital is selected via header or session
                $hospitalId = $request->header('X-Hospital-Id') ?? session('current_hospital_id');

                if ($hospitalId) {
                    app()->instance('current_hospital_id', (int) $hospitalId);
                }
                // If no hospital selected, super admin sees all data (no scope applied)
            } else {
                // Regular users are bound to their hospital
                if ($user->hospital_id) {
                    app()->instance('current_hospital_id', $user->hospital_id);
                }
            }
        }

        return $next($request);
    }
}
