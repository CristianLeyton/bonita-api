<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Nuevo Pedido Recibido',
            'message' => "Se ha recibido un nuevo pedido de {$this->order->email}",
            'icon' => 'heroicon-o-envelope',
            'iconColor' => 'success',
            'order_id' => $this->order->id,
        ];
    }

    public function toFilament($notifiable): FilamentNotification
    {
        return FilamentNotification::make()
            ->title('Nuevo Pedido Recibido')
            ->icon('heroicon-o-envelope')
            ->iconColor('success')
            ->body("Se ha recibido un nuevo pedido de {$this->order->email}")
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Ver Pedido')
                    ->url(route('filament.admin.resources.orders.edit', ['record' => $this->order->id]))
                    ->button(),
            ])
            ->persistent();
    }
}
