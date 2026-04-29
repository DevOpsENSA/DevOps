<?php

namespace App\Http\Middleware;

use App\Models\Compte;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $mail = (string) $request->header('X-User-Mail', '');
        $isAdmin = Compte::query()
            ->where('mail', $mail)
            ->where('status', 'admin')
            ->exists();

        if (! $isAdmin) {
            return response()->json([
                'message' => 'Only admin can upload lessons. Send X-User-Mail of an admin account.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
