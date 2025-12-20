<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $previousStatus;
    public string $newStatus;
    public ?string $notes;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, string $previousStatus, string $newStatus, ?string $notes = null)
    {
        $this->order = $order;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->notes = $notes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Status Update - ' . $this->order->order_code,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.status-update',
            with: [
                'order' => $this->order,
                'previousStatus' => $this->getStatusLabel($this->previousStatus),
                'newStatus' => $this->getStatusLabel($this->newStatus),
                'notes' => $this->notes,
                'trackingUrl' => route('client.orders.track', ['order_code' => $this->order->order_code]),
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
        return [];
    }

    /**
     * Get human-readable status label
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            Order::STATUS_AWAITING_PAYMENT => 'Awaiting Payment',
            Order::STATUS_PAID => 'Paid',
            Order::STATUS_PROCESSING => 'Processing',
            Order::STATUS_PICKUP_READY => 'Ready for Pickup',
            Order::STATUS_COMPLETED => 'Completed',
            Order::STATUS_CANCELLED => 'Cancelled',
        ];

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}