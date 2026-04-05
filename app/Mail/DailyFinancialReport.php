<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class DailyFinancialReport extends Mailable
{
    use Queueable, SerializesModels;

    public $totales;
    public $financialData;
    public $fecha;

    public function __construct($totales, $financialData, $fecha)
    {
        $this->totales = $totales;
        $this->financialData = $financialData;
        $this->fecha = $fecha;
    }

    public function build()
    {
        $pdf = Pdf::loadView('emails.pdf_daily_report', [
            'totales' => $this->totales,
            'financialData' => $this->financialData,
            'fecha' => $this->fecha
        ]);

        return $this->subject('Corte Diario EDUMEX - ' . $this->fecha)
            ->view('emails.daily_report_body')
            ->attachData($pdf->output(), 'EDUMEX_RENDIMIENTO_FINANCIERO_' . str_replace(' ', '_', $this->fecha) . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
