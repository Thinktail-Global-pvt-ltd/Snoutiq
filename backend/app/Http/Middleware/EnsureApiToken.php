<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $auth = $request->header('Authorization');
        if (! $auth || ! preg_match('/^Bearer\\s+(.+)$/i', $auth, $m)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $plain = $m[1];
        $hash = hash('sha256', $plain);

        $tables = [
            'users',
            'vet_registerations_temp',
            'doctors',
            'receptionists',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasColumn($table, 'api_token_hash') || ! Schema::hasColumn($table, 'api_token_expires_at')) {
                continue;
            }
            $match = DB::table($table)
                ->where('api_token_hash', $hash)
                ->where('api_token_expires_at', '>', now())
                ->exists();

            if ($match) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Unauthorized'], 401);

    }
}
