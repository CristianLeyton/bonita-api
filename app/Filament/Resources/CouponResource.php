<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $modelLabel = 'cupón';
    protected static ?string $pluralModelLabel = 'cupones';
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Tienda';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del cupón')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->validationMessages([
                        'unique' => 'Este nombre ya está en uso',
                        'required' => 'El nombre del cupón es requerido',
                        'max' => 'El nombre no puede superar los 255 caracteres',
                    ]),

                Forms\Components\TextInput::make('code')
                    ->label('Código del cupón')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->helperText('Código único que los clientes usarán para aplicar el descuento')
                    ->validationMessages([
                        'required' => 'El código del cupón es requerido',
                        'unique' => 'Este código ya está en uso',
                        'max' => 'El código no puede superar los 50 caracteres',
                    ]),

                Forms\Components\TextInput::make('discount_percentage')
                    ->label('Porcentaje de descuento')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%')
                    ->helperText('Porcentaje de descuento que se aplicará (ej: 25.50 para 25.5%)')
                    ->validationMessages([
                        'required' => 'El porcentaje de descuento es requerido',
                        'numeric' => 'El porcentaje debe ser un número',
                        'min' => 'El porcentaje debe ser mayor a 0',
                        'max' => 'El porcentaje no puede superar el 100%',
                    ]),

                Forms\Components\Toggle::make('is_active')
                    ->label('Cupón activo')
                    ->default(true)
                    ->helperText('Solo los cupones activos pueden ser utilizados por los clientes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Código copiado al portapapeles'),

                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('Descuento')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos los cupones')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->hidden(),
                Tables\Actions\EditAction::make()
                    ->button()
                    ->size(\Filament\Support\Enums\ActionSize::Small)
                    ->hiddenLabel()
                    ->color('primary')
                    ->modalHeading('Editar cupón')
                    ->modalDescription('Modifica la información del cupón')
                    ->modalSubmitActionLabel('Guardar cambios')
                    ->modalCancelActionLabel('Cancelar'),
                /* Tables\Actions\DeleteAction::make()
                    ->button()
                    ->size(\Filament\Support\Enums\ActionSize::Small)
                    ->hiddenLabel()
                    ->color('danger')
                    ->modalHeading('Eliminar cupón')
                    ->modalDescription('¿Estás seguro de que quieres eliminar este cupón? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->modalCancelActionLabel('Cancelar'), */
            ])
            ->bulkActions([
/*                 Tables\Actions\BulkActionGroup::make([
                     Tables\Actions\DeleteBulkAction::make(), 
                ]), */
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }
}
