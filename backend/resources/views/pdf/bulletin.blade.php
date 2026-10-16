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
        th { background: #eaf2fb; }
        .total { font-size: 14px; font-weight: bold; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="bandeau">
        <h1>Relevé de notes</h1>
        <div>Notes publiées uniquement{{ $releve['semestre'] ? ' — semestre '.$releve['semestre'] : '' }}</div>
    </div>
    <p>
        {{ $releve['nom_complet'] }} — {{ $releve['matricule'] }}<br>
        {{ $releve['classe'] }} · {{ $releve['filiere'] }} · {{ $releve['niveau'] }}<br>
        {{ $releve['libelle_annee'] ?? $releve['code_annee'] }}
    </p>
    <table>
        <thead>
            <tr>
                <th>Matière</th>
                <th>Coefficient</th>
                <th>Notes publiées</th>
                <th>Moyenne</th>
            </tr>
        </thead>
        <tbody>
            @foreach($releve['matieres'] as $matiere)
                <tr>
                    <td>{{ $matiere['libelle_matiere'] }}</td>
                    <td>{{ $matiere['coefficient'] }}</td>
                    <td>
                        @foreach($matiere['evaluations'] as $evaluation)
                            @if($evaluation['note'] !== null)
                                {{ $evaluation['type_evaluation'] }} : {{ number_format((float) $evaluation['note'], 2, ',', ' ') }}/20
                                @if(!$loop->last) · @endif
                            @endif
                        @endforeach
                    </td>
                    <td>{{ $matiere['moyenne'] !== null ? number_format((float) $matiere['moyenne'], 2, ',', ' ') : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="total">Moyenne générale : {{ $releve['moyenne_generale'] !== null ? number_format((float) $releve['moyenne_generale'], 2, ',', ' ') : 'Moyenne non disponible' }}</p>
</body>
</html>
