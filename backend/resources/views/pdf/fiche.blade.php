<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        h1 { font-size: 18px; color: #1f4e79; margin-bottom: 4px; }
        .bandeau { background: #1f4e79; color: #fff; padding: 12px 16px; }
        .avertissement { border: 1px solid #1f4e79; padding: 8px; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        td, th { border: 1px solid #cbd5e1; padding: 6px; text-align: left; }
        th { background: #eaf2fb; }
        .montant { font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="bandeau">
        <h1>Fiche de versement</h1>
        <div>Plateforme scolaire — document pédagogique. Paiement à effectuer à la banque (référence d’étude : UBA).</div>
    </div>
    <p class="avertissement">Ce document n’atteste pas le paiement. Le statut actuel est « en attente de paiement ». Aucune somme n’est encaissée par la plateforme.</p>
    @if($fiche->mode_justificatif === 'simulation')
        <p class="avertissement">SIMULATION : un justificatif fictif a été déposé pour les tests. Aucune transaction bancaire n’a eu lieu.</p>
    @endif
    <table>
        <tr><th>Numéro de fiche</th><td>{{ $fiche->numero_fiche }}</td></tr>
        <tr><th>Date de génération</th><td>{{ optional($fiche->date_generation)->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Matricule</th><td>{{ $fiche->matricule }}</td></tr>
        <tr><th>Nom et prénoms</th><td>{{ $fiche->etudiant->utilisateur->nomComplet() }}</td></tr>
        <tr><th>Année scolaire</th><td>{{ $fiche->tranche->annee->libelle_annee ?? $fiche->tranche->code_annee }}</td></tr>
        <tr><th>Filière</th><td>{{ $fiche->tranche->filiere->libelle_filiere ?? '' }}</td></tr>
        <tr><th>Niveau</th><td>{{ $fiche->tranche->niveau->libelle_niveau ?? '' }}</td></tr>
        <tr><th>Classe</th><td>{{ $classe->libelle_classe ?? '—' }}</td></tr>
        <tr><th>Description</th><td>{{ $fiche->tranche->libelle_tranche }}</td></tr>
        <tr><th>Montant</th><td class="montant">{{ number_format($fiche->tranche->montant_tranche, 0, ',', ' ') }} FCFA</td></tr>
        <tr><th>Date limite</th><td>{{ optional($fiche->tranche->date_limite)->format('d/m/Y') }}</td></tr>
        <tr><th>Moyen de paiement</th><td>Versement à la banque. La plateforme ne débite aucun compte.</td></tr>
    </table>
    <p>Zone réservée à la banque : cachet et validation du caissier.</p>
    <p style="border: 1px dashed #94a3b8; height: 80px; padding: 8px;">Cachet</p>
</body>
</html>
