<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/fpdf/fpdf.php';

class LaVingtainePDF extends FPDF
{
    private int $orderId;
    private array $orderData;
    private array $items;
    private array $professional;

    public function __construct(int $orderId, array $orderData, array $items, array $professional)
    {
        parent::__construct();
        $this->orderId = $orderId;
        $this->orderData = $orderData;
        $this->items = $items;
        $this->professional = $professional;
    }

    private function pdfText(string $text): string
    {
        $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $text);
        return $converted === false ? $text : $converted;
    }

    public function Header(): void
    {
        // Logo / Title
        $this->SetFont('Helvetica', 'B', 20);
        $this->SetTextColor(74, 10, 26); // #4a0a1a bordeaux
        $this->Cell(130, 10, $this->pdfText('LA VINGTAINE'), 0, 0, 'L');

        // Document title
        $this->SetFont('Helvetica', 'B', 12);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(60, 10, $this->pdfText('BON DE COMMANDE B2B'), 0, 1, 'R');

        // Subtitle / Agency info
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(130, 5, $this->pdfText('Sélection exclusive de Champagnes, Vins & Spiritueux'), 0, 0, 'L');
        $this->Cell(60, 5, $this->pdfText('Commande N° ') . str_pad((string)$this->orderId, 5, '0', STR_PAD_LEFT), 0, 1, 'R');

        $this->Cell(130, 5, $this->pdfText('12 Avenue de Champagne, 51100 Reims'), 0, 0, 'L');
        $this->Cell(60, 5, $this->pdfText('Date : ') . date('d/m/Y', strtotime($this->orderData['created_at'] ?? 'now')), 0, 1, 'R');

        // Divider line
        $this->SetDrawColor(212, 175, 55); // #d4af37 gold
        $this->SetLineWidth(0.8);
        $this->Line(10, 32, 200, 32);
        $this->Ln(12);
    }

    public function Footer(): void
    {
        $this->SetY(-25);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 4, 'La Vingtaine - S.A.S au capital de 50 000 EUR - RCS Reims B 000 000 000 - TVA FR 00 000000000', 0, 1, 'C');
        $this->Cell(0, 4, 'Vente exclusive aux professionnels - L\'abus d\'alcool est dangereux pour la santé, à consommer avec modération.', 0, 1, 'C');
        $this->Cell(0, 4, 'Page ' . $this->PageNo() . ' / {nb}', 0, 0, 'C');
    }

    public function buildPdf(): string
    {
        $this->AliasNbPages();
        $this->AddPage();
        $this->SetAutoPageBreak(true, 30);

        // Client Section
        $this->SetFillColor(248, 246, 240);
        $this->Rect(10, 38, 190, 26, 'F');
        
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetTextColor(74, 10, 26);
        $this->SetXY(14, 41);
        $this->Cell(180, 5, $this->pdfText('INFORMATIONS CLIENT PROFESSIONNEL'), 0, 1, 'L');

        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(50, 50, 50);
        $this->SetX(14);
        $this->Cell(180, 5, $this->pdfText('Société : ' . ($this->professional['company_name'] ?? $this->orderData['company_name'] ?? 'N/A')), 0, 1, 'L');
        $this->SetX(14);
        $this->Cell(180, 5, $this->pdfText('Contact / E-mail : ' . ($this->professional['customer_email'] ?? $this->orderData['customer_email'] ?? 'N/A')), 0, 1, 'L');

        $this->Ln(8);

        // Table Header
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetFillColor(74, 10, 26);
        $this->SetTextColor(255, 255, 255);
        
        $this->Cell(85, 8, $this->pdfText(' Désignation'), 1, 0, 'L', true);
        $this->Cell(35, 8, $this->pdfText(' Région'), 1, 0, 'L', true);
        $this->Cell(20, 8, $this->pdfText(' Qté'), 1, 0, 'C', true);
        $this->Cell(25, 8, ' P.U. HT', 1, 0, 'R', true);
        $this->Cell(25, 8, ' Total HT', 1, 1, 'R', true);

        // Table Body
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(50, 50, 50);
        $fill = false;

        $subtotal = 0.0;
        foreach ($this->items as $item) {
            $name = $this->pdfText((string)($item['name'] ?? 'Produit'));
            $region = $this->pdfText((string)($item['region'] ?? ''));
            $qty = (int)($item['qty'] ?? $item['quantity'] ?? 1);
            if ($qty < 1) {
                $qty = 1;
            }
            $price = (float)($item['price'] ?? 0);
            $lineTotal = $qty * $price;
            $subtotal += $lineTotal;

            $this->SetFillColor(252, 251, 249);
            $this->Cell(85, 7, ' ' . $name, 1, 0, 'L', $fill);
            $this->Cell(35, 7, ' ' . $region, 1, 0, 'L', $fill);
            $this->Cell(20, 7, (string)$qty, 1, 0, 'C', $fill);
            $this->Cell(25, 7, number_format($price, 2, ',', ' ') . ' EUR ', 1, 0, 'R', $fill);
            $this->Cell(25, 7, number_format($lineTotal, 2, ',', ' ') . ' EUR ', 1, 1, 'R', $fill);

            $fill = !$fill;
        }

        $this->Ln(4);

        // Totals Box
        $vat = $subtotal * 0.20;
        $totalTtc = $subtotal + $vat;

        $this->SetX(110);
        $this->Cell(50, 6, $this->pdfText('Total HT :'), 1, 0, 'L');
        $this->Cell(40, 6, number_format($subtotal, 2, ',', ' ') . ' EUR', 1, 1, 'R');

        $this->SetX(110);
        $this->Cell(50, 6, $this->pdfText('TVA (20%) :'), 1, 0, 'L');
        $this->Cell(40, 6, number_format($vat, 2, ',', ' ') . ' EUR', 1, 1, 'R');

        $this->SetFont('Helvetica', 'B', 10);
        $this->SetFillColor(248, 246, 240);
        $this->SetX(110);
        $this->Cell(50, 8, $this->pdfText('Total TTC :'), 1, 0, 'L', true);
        $this->Cell(40, 8, number_format($totalTtc, 2, ',', ' ') . ' EUR', 1, 1, 'R', true);

        // Conditions & Validations note
        $this->Ln(15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->MultiCell(190, 4, $this->pdfText("Commande enregistrée et validée sous réserve des stocks disponibles. Minimum de commande B2B : 300 EUR HT.\nRèglement par virement bancaire à 30 jours fin de mois."), 0, 'L');

        return $this->Output('S');
    }
}

function generateOrderPdfOutput(int $orderId, array $orderData, array $items, array $professional): string
{
    $pdf = new LaVingtainePDF($orderId, $orderData, $items, $professional);
    return $pdf->buildPdf();
}
