<?php

namespace App\Services;

use App\Models\FicheVersement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class DocumentPdfService
{
    public function fiche(FicheVersement $fiche): Response
    {
        $fiche->load('etudiant.utilisateur', 'tranche.filiere', 'tranche.niveau', 'tranche.annee');
        $inscription = $fiche->etudiant->inscriptions()
            ->with('classe')
            ->where('code_annee', $fiche->tranche->code_annee)
            ->first();

        return $this->reponse('pdf.fiche', [
            'fiche' => $fiche,
            'classe' => $inscription?->classe,
        ], 'fiche-'.$fiche->numero_fiche.'.pdf');
    }

    public function recu(FicheVersement $fiche): Response
    {
        $fiche->load('etudiant.utilisateur', 'tranche', 'verificateur');

        return $this->reponse('pdf.recu', ['fiche' => $fiche], 'recu-'.$fiche->numero_recu.'.pdf');
    }

    public function bulletin(array $releve): Response
    {
        return $this->reponse('pdf.bulletin', ['releve' => $releve], 'bulletin-'.$releve['matricule'].'.pdf');
    }

    private function reponse(string $vue, array $donnees, string $nom): Response
    {
        $pdf = Pdf::loadView($vue, $donnees)->setPaper('a4');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nom.'"',
        ]);
    }
}
