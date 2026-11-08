<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TicketsMail extends Mailable
{
    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ваши билеты KinoBook, бронь №'.$this->order->id);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets');
    }
}