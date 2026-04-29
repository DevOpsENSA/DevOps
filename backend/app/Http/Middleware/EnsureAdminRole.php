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
        $compte = Compte::query()
            ->where('mail', $mail)
            ->where('status', 'admin')
            ->first();

        if (! $compte) {
            return response()->json([
                'message' => 'Action réservée aux administrateurs.',
            ], Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('admin_compte', $compte);

        return $next($request);
    }
}
