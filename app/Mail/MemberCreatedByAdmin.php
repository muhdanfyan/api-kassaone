<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberCreatedByAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public $member;
    public $temporaryPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(Member $member, string $temporaryPassword)
    {
        $this->member = $member;
        $this->temporaryPassword = $temporaryPassword;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Akun Koperasi Anda Telah Dibuat',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.member-created',
            with: [
                'memberName' => $this->member->full_name,
                'username' => $this->member->username,
                'password' => $this->temporaryPassword,
                'loginUrl' => config('app.frontend_url', config('app.url')) . '/login',
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
}
