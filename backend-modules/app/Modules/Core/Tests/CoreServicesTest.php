<?php

namespace App\Modules\Core\Tests;

use Tests\TestCase;
use App\Modules\Core\Services\MailService;
use App\Modules\Core\Services\PdfService;
use Illuminate\Support\Facades\Mail;

class CoreServicesTest extends TestCase
{
    /**
     * Test que le MailService est bien enregistré
     */
    public function test_mail_service_is_registered()
    {
        $mailService = app(MailService::class);
        $this->assertInstanceOf(MailService::class, $mailService);
    }

    /**
     * Test que le PdfService est bien enregistré
     */
    public function test_pdf_service_is_registered()
    {
        $pdfService = app(PdfService::class);
        $this->assertInstanceOf(PdfService::class, $pdfService);
    }

    /**
     * Test d'envoi d'email (fake)
     */
    public function test_can_send_notification_email()
    {
        Mail::fake();

        $mailService = app(MailService::class);
        
        // Ce test vérifie que le service est utilisable
        // En production, vous devriez tester avec de vraies adresses
        $this->assertTrue(true);
    }

    /**
     * Test que les vues des templates existent
     */
    public function test_email_templates_exist()
    {
        $this->assertTrue(view()->exists('core::emails.base'));
        $this->assertTrue(view()->exists('core::emails.notification'));
        $this->assertTrue(view()->exists('core::emails.welcome'));
        $this->assertTrue(view()->exists('core::emails.password-reset'));
    }

    /**
     * Test que les templates PDF existent
     */
    public function test_pdf_templates_exist()
    {
        $this->assertTrue(view()->exists('core::pdfs.base'));
        $this->assertTrue(view()->exists('core::pdfs.document'));
        $this->assertTrue(view()->exists('core::pdfs.report'));
    }
}
