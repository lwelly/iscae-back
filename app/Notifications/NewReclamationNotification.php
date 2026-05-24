<?php

namespace App\Notifications;

use App\Models\Reclamation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReclamationNotification extends Notification
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
            'type'             => 'new_reclamation',
            'reclamation_id'   => $this->reclamation->id,
            'reference_number' => $this->reclamation->reference_number,
            'student_name'     => $this->reclamation->student?->full_name,
            'module'           => $this->reclamation->module?->nom ?? $this->reclamation->module?->name,
            'type_rec'         => $this->reclamation->type,
            'priorite'         => $this->reclamation->priorite,
            'message'          => 'Nouvelle réclamation soumise : ' . $this->reclamation->reference_number,
        ];
    }
}
