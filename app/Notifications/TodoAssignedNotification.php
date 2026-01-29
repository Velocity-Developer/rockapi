<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TodoAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $todo;

    public $assignedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct($todo, $assignedBy)
    {
        $this->todo = $todo;
        $this->assignedBy = $assignedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Todo Baru Ditugaskan',
            'message' => "{$this->assignedBy->name} menugaskan Anda todo: {$this->todo->title}",
            'excerpt' => Str::limit($this->todo->description ?? '', 100),
            'todo_id' => $this->todo->id,
            'todo_title' => $this->todo->title,
            'priority' => $this->todo->priority,
            'due_date' => $this->todo->due_date?->format('Y-m-d H:i'),
            'assigned_by' => $this->assignedBy->name,
            'assigned_by_avatar' => $this->assignedBy->avatar,
            'type' => 'todo_assigned',
            'url' => "/todo/{$this->todo->id}",
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Todo Baru Ditugaskan')
            ->greeting('Halo '.$notifiable->name.',')
            ->line("{$this->assignedBy->name} telah menugaskan todo baru kepada Anda:")
            ->line('**'.$this->todo->title.'**')
            ->line($this->todo->description ?? '')
            ->line('Prioritas: '.ucfirst($this->todo->priority))
            ->when($this->todo->due_date, function ($message) {
                $message->line('Deadline: '.$this->todo->due_date->format('d M Y H:i'));
            })
            ->action('Lihat Todo', url("/todo/{$this->todo->id}"))
            ->line('Terima kasih telah menggunakan aplikasi kami!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'todo_id' => $this->todo->id,
            'title' => $this->todo->title,
            'assigned_by' => $this->assignedBy->name,
        ];
    }
}
