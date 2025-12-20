<?php

namespace AdultDate\FilamentWirechat\Jobs;

use AdultDate\FilamentWirechat\Events\MessageCreated;
use AdultDate\FilamentWirechat\Facades\Wirechat;
use AdultDate\FilamentWirechat\Models\Message;
use AdultDate\FilamentWirechat\Models\Participant;
use AdultDate\FilamentWirechat\Traits\InteractsWithPanel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithPanel;

    /**
     * Create a new job instance.
     */
    protected $auth;

    protected $messagesTable;

    protected $participantsTable;

    public function __construct(public Message $message, ?string $panel = null)
    {
        $this->resolvePanel($panel);
        //
        // Use the messages queue from config
        $this->onQueue(Wirechat::messagesQueue());
        $this->auth = auth()->user();

        // Get table
        $this->messagesTable = (new Message)->getTable();
        $this->participantsTable = (new Participant)->getTable();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Broadcast to the conversation channel for all participants
        event(new MessageCreated($this->message, $this->panel));
    }
}
