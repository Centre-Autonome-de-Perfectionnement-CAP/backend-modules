<?php

namespace Tests\Feature\Verif;

use App\Modules\Core\Support\WhatsAppText;
use App\Modules\Demandes\Services\WhatsAppService;
use Tests\TestCase;

/**
 * Messages WhatsApp : structurés (titre, lignes vides, champs en gras) et sans émoji.
 */
class WhatsAppTextTest extends TestCase
{
    private const EMOJI = '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}]/u';

    public function test_compose_structure(): void
    {
        $msg = WhatsAppText::compose('Paiement validé', [
            'Bonjour Luc,', '', WhatsAppText::fields(['Montant' => '10 000 FCFA', 'Motif' => '', 'Référence' => 'PAY-1']),
        ]);
        $this->assertSame("*CAP-EPAC*\n_Paiement validé_\n\nBonjour Luc,\n\n*Montant :* 10 000 FCFA\n*Référence :* PAY-1", $msg);
    }

    public function test_demandes_templates_are_structured_and_emoji_free(): void
    {
        $wa = app(WhatsAppService::class);
        $all = [
            $wa->templateSoumission('ATT-1', 'Attestation', 'Luc'),
            $wa->templateComplementEtudiant('ATT-1', ['Acte', 'Relevé'], 'Luc'),
            $wa->templatePret('ATT-1', 'Attestation', 'Luc'),
            $wa->templateRejete('ATT-1', 'Attestation', 'Pièce illisible', 'Luc'),
            $wa->templateSousReserve('ATT-1', 'Attestation', 'Pièce illisible', 'Luc'),
            $wa->templateMessageSecretariat('Luc', 'Merci de passer.', 'Awa'),
            $wa->templateRemis('ATT-1', 'Attestation', 'Luc'),
            $wa->templateNouvelleDemandeSecretaire('Awa', 'ATT-1', 'Attestation', 'Luc', 'M1'),
            $wa->templateComplementSecretaire('Awa', 'ATT-1', 'Luc', 2),
            $wa->templateActeurDossier('Awa', 'Comptable', 'ATT-1', 'Attestation', 'Luc', 'M1', 'RAS'),
            $wa->templateCorrectionCircuit('Awa', 'Comptable', 'ATT-1', 'Attestation', 'Luc', 'M1', null),
            $wa->templateDossierDirection('Awa', 'Luc', 'ATT-1'),
            $wa->templateDirecteurSigne('Awa', 'Luc', 'ATT-1', 'Attestation', 'M1'),
        ];
        foreach ($all as $msg) {
            $this->assertStringStartsWith("*CAP-EPAC*\n_", $msg);
            $this->assertStringContainsString("\n\n", $msg);
            $this->assertDoesNotMatchRegularExpression(self::EMOJI, $msg);
            $this->assertStringNotContainsString("\n\n\n", $msg);
        }
        $this->assertStringNotContainsString('Motif', $all[10], 'champ vide omis');
    }
}
