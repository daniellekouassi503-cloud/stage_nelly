<?php

return [
    /*
    | Le mot de confirmation empêche qu'un simple clic valide une simulation.
    | Aucune passerelle bancaire n'est branchée : le paiement réel reste à la banque.
    */
    'simulation_active' => env('PAIEMENT_SIMULATION', true),
    'simulation_confirmation' => env('PAIEMENT_SIMULATION_CONFIRMATION', 'SIMULATION'),

    'fichiers_autorises' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'odt', 'png', 'jpg', 'jpeg'],
    'taille_max_ko' => 10240,
];
