<?php

namespace App\Notifications;

use App\Models\Reclamation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReclamationUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Reclamation $reclamation)
    {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'reclamation_updated',
            'reclamation_id'   => $this->reclamation->id,
            'reference_number' => $this->reclamation->reference_number,
            'status'           => $this->reclamation->status,
            'message'          => 'Votre réclamation ' . $this->reclamation->reference_number . ' a été mise à jour : ' . $this->reclamation->status,
        ];
    }
}
