<?php

namespace Tests\Unit;

use App\Services\MoyenneService;
use PHPUnit\Framework\TestCase;

class MoyenneServiceTest extends TestCase
{
    public function test_moyenne_de_matiere_ignore_l_absence_et_arrondit(): void
    {
        $service = new MoyenneService;

        $this->assertSame('10.67', $service->moyenneMatiere(['10', '10', '12']));
        $this->assertSame('15.00', $service->moyenneMatiere([15]));
        $this->assertNull($service->moyenneMatiere([]));
    }

    public function test_moyenne_generale_ponderee(): void
    {
        $service = new MoyenneService;

        $moyenne = $service->moyenneGenerale([
            ['moyenne' => '12.50', 'coefficient' => '3'],
            ['moyenne' => '10.00', 'coefficient' => '1'],
            ['moyenne' => null, 'coefficient' => '2'],
        ]);

        $this->assertSame('11.88', $moyenne);
        $this->assertSame('Moyenne non disponible', $service->libelle(null));
    }
}
