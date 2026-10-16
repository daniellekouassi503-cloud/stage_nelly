<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        h1 { font-size: 18px; color: #1f4e79; }
        .bandeau { background: #1f4e79; color: #fff; padding: 12px 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        td, th { border: 1px solid #cbd5e1; padding: 6px; text-align: left; }
        th { background: #eaf2fb; width: 40%; }
    </style>
</head>
<body>
    <div class="bandeau">
        <h1>Reçu de paiement</h1>
        <div>Délivré après vérification administrative. Ce reçu n’est pas une fiche de versement.</div>
    </div>
    @if($fiche->mode_justificatif === 'simulation')
        <p style="border:1px solid #1f4e79;padding:8px;">Paiement simulé, validé par l’administration pour un test. Aucune transaction bancaire réelle n’a été effectuée.</p>
    @endif
    <table>
        <tr><th>Numéro de reçu</th><td>{{ $fiche->numero_recu }}</td></tr>
        <tr><th>Fiche de versement</th><td>{{ $fiche->numero_fiche }}</td></tr>
        <tr><th>Étudiant</th><td>{{ $fiche->etudiant->utilisateur->nomComplet() }} ({{ $fiche->matricule }})</td></tr>
        <tr><th>Tranche</th><td>{{ $fiche->tranche->libelle_tranche }}</td></tr>
        <tr><th>Montant</th><td>{{ number_format($fiche->tranche->montant_tranche, 0, ',', ' ') }} FCFA</td></tr>
        <tr><th>Date de paiement</th><td>{{ optional($fiche->date_paiement)->format('d/m/Y') }}</td></tr>
        <tr><th>Vérifié le</th><td>{{ optional($fiche->date_verification)->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Vérificateur</th><td>{{ $fiche->verificateur?->nomComplet() }}</td></tr>
        <tr><th>Mode</th><td>{{ $fiche->mode_justificatif === 'simulation' ? 'Simulation' : 'Justificatif bancaire' }}</td></tr>
    </table>
</body>
</html>
