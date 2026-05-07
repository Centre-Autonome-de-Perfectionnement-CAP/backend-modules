<?php

namespace App\Modules\Core\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Response;
use Exception;

class PdfService
{
    /**
     * Générer un PDF à partir d'une vue
     *
     * @param string $view
     * @param array $data
     * @param array $options
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePdf(string $view, array $data = [], array $options = [])
    {
        \Log::info('PdfService: Début génération PDF', ['view' => $view, 'data_keys' => array_keys($data)]);
        
        try {
            $pdf = Pdf::loadView($view, $data);

            // Appliquer les options
            if (isset($options['orientation'])) {
                $pdf->setPaper('A4', $options['orientation']);
            }

            if (isset($options['paper_size'])) {
                $pdf->setPaper($options['paper_size'], $options['orientation'] ?? 'portrait');
            }

            \Log::info('PdfService: PDF généré avec succès', ['view' => $view]);

            return $pdf;
        } catch (\Exception $e) {
            \Log::error('PdfService: Erreur génération PDF', [
                'view' => $view,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Générer un PDF avec un template prédéfini
     *
     * @param string $template
     * @param array $data
     * @param array $options
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateWithTemplate(string $template, array $data = [], array $options = [])
    {
        $view = "core::pdfs.{$template}";
        return $this->generatePdf($view, $data, $options);
    }

    /**
     * Télécharger un PDF généré
     *
     * @param string $view
     * @param array $data
     * @param string $filename
     * @param array $options
     * @return Response
     */
    public function downloadPdf(string $view, array $data = [], string $filename = 'document.pdf', array $options = [])
    {
        \Log::info('PdfService: Début téléchargement PDF', ['filename' => $filename]);
        
        try {
            $pdf = $this->generatePdf($view, $data, $options);
            \Log::info('PdfService: Téléchargement PDF prêt', ['filename' => $filename]);
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('PdfService: Erreur téléchargement PDF', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Télécharger un PDF avec un template prédéfini
     *
     * @param string $template
     * @param array $data
     * @param string $filename
     * @param array $options
     * @return Response
     */
    public function downloadWithTemplate(string $template, array $data = [], string $filename = 'document.pdf', array $options = [])
    {
        $view = "core::pdfs.{$template}";
        return $this->downloadPdf($view, $data, $filename, $options);
    }

    /**
     * Afficher un PDF dans le navigateur
     *
     * @param string $view
     * @param array $data
     * @param array $options
     * @return Response
     */
    public function streamPdf(string $view, array $data = [], array $options = [])
    {
        $pdf = $this->generatePdf($view, $data, $options);
        return $pdf->stream();
    }

    /**
     * Afficher un PDF avec un template prédéfini
     *
     * @param string $template
     * @param array $data
     * @param array $options
     * @return Response
     */
    public function streamWithTemplate(string $template, array $data = [], array $options = [])
    {
        $view = "core::pdfs.{$template}";
        return $this->streamPdf($view, $data, $options);
    }

    /**
     * Sauvegarder un PDF sur le disque
     *
     * @param string $view
     * @param array $data
     * @param string $path
     * @param array $options
     * @return bool
     */
    public function savePdf(string $view, array $data = [], string $path, array $options = []): bool
    {
        try {
            $pdf = $this->generatePdf($view, $data, $options);
            $pdf->save($path);
            return true;
        } catch (Exception $e) {
            \Log::error('Erreur lors de la sauvegarde du PDF: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sauvegarder un PDF avec un template prédéfini
     *
     * @param string $template
     * @param array $data
     * @param string $path
     * @param array $options
     * @return bool
     */
    public function saveWithTemplate(string $template, array $data = [], string $path, array $options = []): bool
    {
        $view = "core::pdfs.{$template}";
        return $this->savePdf($view, $data, $path, $options);
    }

    /**
     * Obtenir le contenu d'un PDF en string
     *
     * @param string $view
     * @param array $data
     * @param array $options
     * @return string
     */
    public function getPdfOutput(string $view, array $data = [], array $options = []): string
    {
        $pdf = $this->generatePdf($view, $data, $options);
        return $pdf->output();
    }
}
