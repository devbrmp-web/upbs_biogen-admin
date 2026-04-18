<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $type;
    public ?string $notes;

    /**
     * Create a new message instance.
     * 
     * @param Order $order
     * @param string $type ('awaiting_payment', 'paid', 'processing', 'pickup_ready', 'completed', 'cancelled')
     * @param string|null $notes
     */
    public function __construct(Order $order, string $type, ?string $notes = null)
    {
        $this->order = $order;
        $this->type = $type;
        $this->notes = $notes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            'awaiting_payment' => 'Pesanan Baru UPBS BRMP - ' . $this->order->order_code,
            'paid', 'processing' => 'Pembayaran Diterima - ' . $this->order->order_code,
            'pickup_ready' => 'Pesanan Siap Diambil - ' . $this->order->order_code,
            'completed' => 'Pesanan Selesai - Terima Kasih!',
            'cancelled' => 'Pesanan Dibatalkan - ' . $this->order->order_code,
            default => 'Notifikasi Pesanan - ' . $this->order->order_code,
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $trackingUrl = rtrim(config('app.client_url'), '/') . '/pesanan/' . $this->order->order_code;

        return new Content(
            view: 'emails.orders.notification',
            with: [
                'order' => $this->order,
                'type' => $this->type,
                'notes' => $this->notes,
                'trackingUrl' => $trackingUrl,
                'items' => $this->order->orderItems()->with('variety')->get(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        // Attach PDF for checkout and payment statuses
        if (in_array($this->type, ['awaiting_payment', 'paid', 'processing'])) {
            $attachments[] = Attachment::fromData(fn () => $this->generatePdfContent(), 'INVOICE-' . $this->order->order_code . '.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }

    /**
     * Generate PDF content for the attachment
     */
    protected function generatePdfContent()
    {
        ini_set('memory_limit', '256M');
        
        // Ensure relations are loaded
        $this->order->load(['orderItems.seedLot.variety.commodity', 'orderItems.seedLot.seedClass', 'payment', 'shipment']);
        
        // Calculate correct totals (Snapshot for PDF)
        $this->order->computed_subtotal = $this->order->orderItems->sum(function($item) {
            return $item->quantity * $item->price_at_order;
        });
        $this->order->computed_biaya_layanan = $this->order->computed_subtotal * 0.01;
        $this->order->computed_biaya_aplikasi = 2500;
        $this->order->computed_total = $this->order->computed_subtotal + $this->order->computed_biaya_layanan + $this->order->computed_biaya_aplikasi;

        $logoPath = public_path('images/Logo_Kementerian_Pertanian_Republik_Indonesia.svg.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
        }
        
        $pdf = Pdf::loadView('admin.orders.pdf.invoice', [
            'order' => $this->order,
            'logoBase64' => $logoBase64
        ])
        ->setPaper('A4')
        ->setOption('margin-top', 20)
        ->setOption('margin-right', 15)
        ->setOption('margin-bottom', 20)
        ->setOption('margin-left', 15)
        ->setOption('default_font', 'DejaVu Sans')
        ->setOption('isHtml5ParserEnabled', true);
        
        return $pdf->output();
    }
}
