<?php

namespace App\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class MoyenneService
{
    /**
     * Moyenne de matière : moyenne arithmétique des notes présentes.
     * Devoirs et interrogations ont le même poids (RG8).
     * Une absence de note n'est pas comptée comme zéro (RGF9).
     * Arrondi moitié supérieure à deux décimales (hypothèse documentée, RGF10).
     *
     * @param  array<int, string|float|int>  $notes
     */
    public function moyenneMatiere(array $notes): ?string
    {
        if ($notes === []) {
            return null;
        }

        $somme = BigDecimal::zero();
        foreach ($notes as $note) {
            $somme = $somme->plus(BigDecimal::of((string) $note));
        }

        return (string) $somme
            ->dividedBy(count($notes), 2, RoundingMode::HalfUp);
    }

    /**
     * Moyenne générale = somme(moyenne matière × coefficient) / somme(coefficients).
     * Seules les matières qui ont au moins une note entrent au dénominateur (RGF10).
     *
     * @param  array<int, array{moyenne: ?string, coefficient: string|float|int}>  $matieres
     */
    public function moyenneGenerale(array $matieres): ?string
    {
        $numerateur = BigDecimal::zero();
        $denominateur = BigDecimal::zero();

        foreach ($matieres as $matiere) {
            if ($matiere['moyenne'] === null) {
                continue;
            }

            $coefficient = BigDecimal::of((string) $matiere['coefficient']);
            $numerateur = $numerateur->plus(
                BigDecimal::of($matiere['moyenne'])->multipliedBy($coefficient)
            );
            $denominateur = $denominateur->plus($coefficient);
        }

        if ($denominateur->isZero()) {
            return null;
        }

        return (string) $numerateur->dividedBy($denominateur, 2, RoundingMode::HalfUp);
    }

    public function libelle(?string $moyenne): string
    {
        return $moyenne ?? 'Moyenne non disponible';
    }
}
