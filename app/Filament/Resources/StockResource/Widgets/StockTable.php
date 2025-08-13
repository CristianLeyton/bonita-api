<?php

namespace App\Filament\Resources\StockResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Product;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\ActionSize;

class StockTable extends BaseWidget
{
    protected int|string|array $columnSpan = 'full'; // Para que ocupe todo el ancho del dashboard

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
            )->defaultSort('quantity','asc')
            ->heading('Stock de productos')
            ->description('Si desea editar por completo un producto, dirigase a "Productos"')
            ->columns([
                ImageColumn::make('primaryImage.url')
                    ->label('Imagen')
                    ->square(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->alignment(Alignment::Center)
                    ->sortable()
                    ->color(fn($state) => match (true) {
                        $state < 5 => 'danger',   // rojo
                        $state < 10 => 'warning', // amarillo
                        default => 'success',     // verde
                    }),
                TextColumn::make('price')
                    ->label('Precio')
                    ->visibleFrom('md')
                    ->money('ARS', locale: 'es_AR')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->visibleFrom('md')
                    ->getStateUsing(fn($record) => $record->price * $record->quantity)
                    ->money('ARS', locale: 'es_AR')
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->button()
                    ->size(ActionSize::Medium)
                    ->hiddenLabel()
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->validationMessages([
                                'required' => 'El nombre es requerido',
                            ])
                            ->required()
                            ->disabled(),
                        Forms\Components\Grid::make(2)->schema([
                            /*                             Forms\Components\FileUpload::make('urlImage')
                                ->label('Imagen')
                                ->image()
                                ->disabled(), */

                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->validationMessages([
                                    'required' => 'La cantidad es requerida',
                                    'min' => 'La cantidad no puede ser negativa',
                                ]),

                            Forms\Components\TextInput::make('price')
                                ->label('Precio')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->validationMessages([
                                    'required' => 'El precio es requerido',
                                    'min' => 'El precio no puede ser negativo',
                                ]),
                        ]),
                    ])
                    ->modalHeading('Editar cantidad y precio'),
            ]);
    }
}
