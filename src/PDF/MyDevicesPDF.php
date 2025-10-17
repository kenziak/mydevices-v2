<?php
namespace GlpiPlugin\Mydevices;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Generowanie PDF z użyciem dompdf.
 * Klasa odpowiada za render HTML -> PDF, korzysta z szablonów w templates/pdf/
 */
class MyDevicesPDF {
    protected $templateDir;

    public function __construct(string $templateDir = __DIR__ . '/../../templates/pdf') {
        $this->templateDir = $templateDir;
    }

    /**
     * Generuje PDF z podanych danych i zwraca binarną treść PDF.
     *
     * @param string $templateFile Nazwa pliku szablonu (np. 'protocol.html.twig' lub plain html)
     * @param array $params Dane do podstawienia w szablonie
     * @return string PDF binary
     */
    public function renderPdf(string $templateFile, array $params = []): string {
        // Wczytaj szablon (proste zastępowanie zmiennych w formacie {{key}})
        $tplPath = rtrim($this->templateDir, '/').'/'.$templateFile;
        $html = file_get_contents($tplPath);
        foreach ($params as $k => $v) {
            $html = str_replace('{{'.$k.'}}', htmlspecialchars((string)$v), $html);
        }

        // Ustawienia dompdf z obsługą polskich znaków
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}