<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()->formId('form'),
            Actions\Action::make('markAsShipped')
                ->label('Marcar como Enviado')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'shipped']);
                    $this->record->updateStock('decrease');
                    Notification::make()
                        ->title('Pedido marcado como enviado')
                        ->body('El stock ha sido actualizado correctamente')
                        ->success()
                        ->send();
                    $this->redirect(OrderResource::getUrl());
                })
                ->visible(fn() => $this->record->status !== 'shipped'),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $oldStatus = $this->record->getOriginal('status');
        $newStatus = $this->record->status;

        if ($oldStatus === 'shipped' && $newStatus !== 'shipped') {
            $this->record->updateStock('increase');
            Notification::make()
                ->title('Estado actualizado')
                ->body('El pedido ha sido actualizado y el stock ha sido devuelto correctamente')
                ->success()
                ->send();
        } elseif ($newStatus === 'shipped' && $oldStatus !== 'shipped') {
            $this->record->updateStock('decrease');
            Notification::make()
                ->title('Estado actualizado')
                ->body('El pedido ha sido actualizado y el stock ha sido reducido correctamente')
                ->success()
                ->send();
        }

        Notification::make()
            ->title('Pedido actualizado')
            ->body('Los cambios han sido guardados correctamente')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return OrderResource::getUrl();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $oldStatus = $this->record->getOriginal('status');
        $newStatus = $data['status'];

        if ($oldStatus === 'shipped' && $newStatus !== 'shipped') {
            $this->record->updateStock('increase');
            Notification::make()
                ->title('Estado actualizado')
                ->body('El pedido ha sido actualizado y el stock ha sido devuelto correctamente')
                ->success()
                ->send();
        } elseif ($newStatus === 'shipped' && $oldStatus !== 'shipped') {
            $this->record->updateStock('decrease');
            Notification::make()
                ->title('Estado actualizado')
                ->body('El pedido ha sido actualizado y el stock ha sido reducido correctamente')
                ->success()
                ->send();
        }

        return $data;
    }
}
