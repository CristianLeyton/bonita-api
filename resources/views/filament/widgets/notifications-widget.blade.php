<x-filament-widgets::widget>
    <x-filament::section>
        <div>
            <h3>Notificaciones</h3>
            @foreach ($this->getNotifications() as $notification)
                <div>
                    <strong>{{ $notification->data['title'] }}</strong>
                    <p>{{ $notification->data['message'] }}</p>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
