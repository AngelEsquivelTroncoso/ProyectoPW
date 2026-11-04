<?php
declare(strict_types=1);

/**
 * Generación del recibo en PDF con FPDF.
 */

require_once __DIR__ . '/../lib/fpdf/fpdf.php';
require_once __DIR__ . '/pedidos.php';
require_once __DIR__ . '/funciones.php';

/**
 * Convierte texto UTF-8 a ISO-8859-1, que es lo que esperan las fuentes
 * base de FPDF.
 */
function pdf_texto(string $texto): string
{
    $convertido = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto);

    return $convertido === false ? $texto : $convertido;
}

/**
 * Devuelve el recibo de un pedido como PDF binario, o null si no existe.
 *
 * Se genera en memoria: no se escribe en disco ni se expone el archivo.
 */
function recibo_pdf(int $pedido_id): ?string
{
    $pedido = pedido_con_cliente($pedido_id);

    if ($pedido === null) {
        return null;
    }

    $lineas = detalle_de_pedido($pedido_id);

    $estados = [
        'pendiente' => 'Pendiente de confirmación',
        'pagado'    => 'Pagado',
        'cancelado' => 'Cancelado',
    ];
    $estado = $estados[$pedido['estado']] ?? (string) $pedido['estado'];

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->SetTitle(pdf_texto('Recibo ' . folio_pedido($pedido_id)));
    $pdf->SetAuthor(pdf_texto('Congreso SysIoT 2026'));
    $pdf->AddPage();

    // ----- Encabezado -----
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetTextColor(10, 16, 40);
    $pdf->Cell(0, 8, pdf_texto('Congreso Internacional de Ingeniería en Sistemas e IoT'), 0, 1, 'C');

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(100, 110, 130);
    $pdf->Cell(0, 5, pdf_texto('SysIoT 2026 · 12 al 14 de noviembre de 2026 · Centro de Convenciones Tecnológico'), 0, 1, 'C');

    $pdf->SetDrawColor(34, 211, 238);
    $pdf->SetLineWidth(0.6);
    $pdf->Line(15, 32, 195, 32);
    $pdf->Ln(6);

    // ----- Título y folio -----
    $pdf->SetTextColor(10, 16, 40);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->Cell(120, 8, pdf_texto('Comprobante de pago'), 0, 0, 'L');

    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(100, 110, 130);
    $pdf->Cell(60, 8, pdf_texto('Folio ' . folio_pedido($pedido_id)), 0, 1, 'R');

    $pdf->Ln(2);

    // ----- Datos del cliente -----
    $filas = [
        'Cliente' => (string) $pedido['cliente_nombre'],
        'Correo'  => (string) $pedido['cliente_correo'],
        'Fecha'   => date('d/m/Y H:i', strtotime((string) $pedido['creado_en'])),
        'Estado'  => $estado,
    ];

    foreach ($filas as $etiqueta => $valor) {
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor(10, 16, 40);
        $pdf->Cell(32, 6, pdf_texto($etiqueta), 0, 0);

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(60, 70, 95);
        $pdf->Cell(0, 6, pdf_texto($valor), 0, 1);
    }

    // ----- Tabla de conceptos -----
    $pdf->Ln(4);

    $pdf->SetFillColor(10, 16, 40);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 9);

    $pdf->Cell(90, 8, pdf_texto('Producto'), 0, 0, 'L', true);
    $pdf->Cell(20, 8, pdf_texto('Cant.'), 0, 0, 'C', true);
    $pdf->Cell(35, 8, pdf_texto('Precio unitario'), 0, 0, 'R', true);
    $pdf->Cell(35, 8, pdf_texto('Subtotal'), 0, 0, 'R', true);
    $pdf->Ln();

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(10, 16, 40);

    $alterno = false;

    foreach ($lineas as $linea) {
        $nombre = pdf_texto((string) $linea['nombre']);

        if (strlen($nombre) > 55) {
            $nombre = substr($nombre, 0, 52) . '...';
        }

        $cantidad   = (int) $linea['cantidad'];
        $unitario   = (float) $linea['precio_unitario'];
        $subtotal   = $unitario * $cantidad;

        $pdf->SetFillColor(238, 242, 250);

        $pdf->Cell(90, 7, $nombre, 0, 0, 'L', $alterno);
        $pdf->Cell(20, 7, (string) $cantidad, 0, 0, 'C', $alterno);
        $pdf->Cell(35, 7, pdf_texto(precio($unitario)), 0, 0, 'R', $alterno);
        $pdf->Cell(35, 7, pdf_texto(precio($subtotal)), 0, 0, 'R', $alterno);
        $pdf->Ln();

        $alterno = !$alterno;
    }

    // ----- Total -----
    $pdf->SetDrawColor(200, 208, 225);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(2);

    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(145, 9, pdf_texto('Total'), 0, 0, 'R');
    $pdf->Cell(35, 9, pdf_texto(precio($pedido['total'])), 0, 1, 'R');

    // ----- Nota -----
    $pdf->Ln(8);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(100, 110, 130);
    $pdf->MultiCell(
        0,
        4,
        pdf_texto(
            'Este comprobante acredita el pago de la inscripción y/o los talleres seleccionados. '
            . 'Presenta el folio en el módulo de registro del congreso.'
        ),
        0,
        'L'
    );

    $pdf->Ln(2);
    $pdf->Cell(
        0,
        4,
        pdf_texto('Generado el ' . date('d/m/Y H:i') . ' · congreso-sysiot.example'),
        0,
        0,
        'L'
    );

    return $pdf->Output('S');
}
