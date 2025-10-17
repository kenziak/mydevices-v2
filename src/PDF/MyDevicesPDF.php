<?php
namespace GlpiPlugin\Mydevices\PDF;

use Mpdf\Mpdf;

/**
 * Klasa MyDevicesPDF
 *
 * Odpowiada za generowanie protokołów w formacie PDF przy użyciu biblioteki mPDF.
 * Na razie jest to tylko szkielet, który zostanie rozwinięty w przyszłości.
 */
class MyDevicesPDF {

    /**
     * Generuje protokół PDF.
     *
     * @param array $assets Tablica z danymi zasobów.
     * @param array $user_data Tablica z danymi użytkownika.
     */
    public function generateProtocol(array $assets, array $user_data) {
        $mpdf = new Mpdf();
        $mpdf->WriteHTML('<h1>Protokół zdawczo-odbiorczy</h1>');
        $mpdf->WriteHTML('<p>Użytkownik: ' . $user_data['name'] . '</p>');

        $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%">';
        $html .= '<thead><tr><th>Nazwa</th><th>Model</th><th>Serial</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($assets as $asset) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($asset['name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($asset['model'] ?? '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($asset['serial'] ?? '-') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $mpdf->WriteHTML($html);
        $mpdf->Output('protocol.pdf', 'D');
        exit;
    }
}