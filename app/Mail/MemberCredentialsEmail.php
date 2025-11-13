<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Member;

class MemberCredentialsEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $member;
    public $temporaryPassword;
    public $completeRegistrationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Member $member, string $temporaryPassword)
    {
        $this->member = $member;
        $this->temporaryPassword = $temporaryPassword;
        $this->completeRegistrationUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/complete-registration';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Informasi Akun Member Koperasi',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.member-credentials',
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
