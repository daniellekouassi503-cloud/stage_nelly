<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifierCompteValide
{
    public function handle(Request $request, Closure $next): Response
    {
        $utilisateur = $request->user();

        if (! $utilisateur) {
            abort(401, 'Authentification requise.');
        }

        if ($utilisateur->statut_compte === 'rejete') {
            abort(403, 'Ce compte a été rejeté.');
        }

        if ($utilisateur->statut_compte !== 'valide') {
            abort(403, 'Le compte est en attente de validation par l’administration.');
        }

        return $next($request);
    }
}
