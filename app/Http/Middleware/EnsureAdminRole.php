<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class EnsureAdminRole {
    public function handle(Request $request, Closure $next, ...$roles): Response {
        $user=$request->user(); abort_unless($user && $user->status==='active' && (!$roles || in_array($user->role,$roles,true)),403);
        return $next($request);
    }
}
