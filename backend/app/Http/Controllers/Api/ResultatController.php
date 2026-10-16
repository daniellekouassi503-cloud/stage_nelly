<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Etudiant;
use App\Services\AccesScolaire;
use App\Services\DocumentPdfService;
use App\Services\EvaluationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResultatController extends Controller
{
    public function __construct(
        private readonly EvaluationService $evaluations,
        private readonly AccesScolaire $acces,
        private readonly DocumentPdfService $pdf,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $etudiant = $this->etudiantDemande($request);
        $annee = $this->annee($request);
        $semestre = $request->filled('semestre') ? (int) $request->integer('semestre') : null;
        $interne = $request->boolean('interne') && $this->peutVoirNonPublie($request, $etudiant);

        return response()->json([
            'donnees' => $this->evaluations->releve($etudiant, $annee, $semestre, ! $interne),
        ]);
    }

    public function bulletin(Request $request): Response
    {
        $etudiant = $this->etudiantDemande($request);
        $annee = $this->annee($request);
        $semestre = $request->filled('semestre') ? (int) $request->integer('semestre') : null;
        $releve = $this->evaluations->releve($etudiant, $annee, $semestre, true);

        return $this->pdf->bulletin($releve);
    }

    private function etudiantDemande(Request $request): Etudiant
    {
        $utilisateur = $request->user();
        $matricule = $request->string('matricule')->toString();

        if ($utilisateur->role === 'etudiant') {
            $propre = $utilisateur->etudiant?->matricule;
            if ($matricule !== '' && $matricule !== $propre) {
                throw new AuthorizationException('Vous ne pouvez pas consulter un autre étudiant.');
            }
            $matricule = $propre ?? '';
        }

        if ($matricule === '') {
            abort(422, 'Le matricule est obligatoire.');
        }

        $etudiant = Etudiant::query()->with('utilisateur')->find($matricule);
        if (! $etudiant) {
            abort(404, 'Étudiant introuvable.');
        }

        $this->acces->exigerEtudiant($utilisateur, $etudiant);

        return $etudiant;
    }

    private function annee(Request $request): string
    {
        $code = $request->string('code_annee')->toString();
        if ($code !== '') {
            return $code;
        }

        return $this->evaluations->anneeCourante()?->code_annee
            ?? abort(422, 'Aucune année scolaire n’est configurée.');
    }

    private function peutVoirNonPublie(Request $request, Etudiant $etudiant): bool
    {
        $role = $request->user()->role;

        return in_array($role, ['administrateur', 'enseignant'], true)
            && $this->acces->peutVoirEtudiant($request->user(), $etudiant);
    }
}
