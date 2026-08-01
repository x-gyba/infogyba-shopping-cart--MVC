<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Página estática de Política de Privacidade (LGPD).
 * Sem model/dado dinâmico — só uma view informativa.
 */
class LegalController extends Controller
{
    /** GET /privacidade */
    public function privacidade(): void
    {
        $this->render('legal/privacidade', [
            'atualizadoEm' => '26/07/2026',
        ]);
    }

    /** GET /termos */
    public function termos(): void
    {
        $this->render('legal/termos', [
            'atualizadoEm' => '26/07/2026',
        ]);
    }

    /** GET /trocas-e-devolucao */
    public function trocasEDevolucao(): void
    {
        $this->render('legal/trocas-devolucao', [
            'atualizadoEm' => '26/07/2026',
        ]);
    }
}
