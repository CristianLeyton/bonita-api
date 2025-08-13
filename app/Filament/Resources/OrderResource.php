<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\ActionSize;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Product;
use App\Models\Color;
use Illuminate\Contracts\View\View;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action as FilamentAction;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Pedidos';

    protected static ?string $modelLabel = 'Pedido';

    protected static ?string $pluralModelLabel = 'Pedidos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Pedido')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label('Asunto')
                            ->required()
                            ->validationMessages([
                                'required' => 'El asunto es requerido',
                            ]),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo')
                            ->email()
                            ->required()
                            ->validationMessages([
                                'required' => 'El correo es requerido',
                                'email' => 'Ingrese un correo valido'
                            ]),
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel(),
                        Forms\Components\TextInput::make('address')
                            ->label('Dirección'),
                        Forms\Components\TextInput::make('postal_code')
                            ->label('Código Postal'),
                        Forms\Components\TextInput::make('follow_number')
                            ->label('Número de seguimiento')
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'viewed' => 'Visto',
                                'paid' => 'Pagado',
                                'shipped' => 'Enviado',
                            ])
                            ->default('pending')
                            ->required()
                            ->validationMessages([
                                'required' => 'El estado es requerido',
                            ]),
                        Forms\Components\TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record) {
                                    $record->calculateTotals();
                                    $component->state($record->total);
                                }
                            }),
                        Forms\Components\Select::make('coupon_id')
                            ->label('Cupón de descuento')
                            ->relationship('coupon', 'code', function ($query) {
                                return $query->active(); // Solo cupones activos
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Seleccionar cupón (opcional)')
                            ->helperText('Aplica un descuento al pedido')
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                static::updateTotal($get, $set);
                            })
                            ->rules([
                                'nullable',
                                'exists:coupons,id',
                            ])
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                // Validación adicional para cupones activos
                                if ($state) {
                                    $coupon = \App\Models\Coupon::find($state);
                                    if (!$coupon || !$coupon->is_active) {
                                        $set('coupon_id', null);
                                        // Mostrar notificación de error
                                        \Filament\Notifications\Notification::make()
                                            ->title('Cupón no válido')
                                            ->body('El cupón seleccionado no está activo.')
                                            ->danger()
                                            ->send();
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal (sin descuento)')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record) {
                                    $record->calculateTotals();
                                    $component->state($record->subtotal);
                                }
                            }),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Descuento aplicado')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record) {
                                    $record->calculateTotals();
                                    $component->state($record->discount_amount);
                                }
                            }),
                        Forms\Components\Textarea::make('message')
                            ->label('Mensaje')
                            ->rows(6),
                    ])->columns(2),

                Forms\Components\Section::make('Productos')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Productos')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Producto')
                                    ->options(Product::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'El nombre de pruducto es requerido',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('price', $product->price);
                                                $set('color_id', null); // Reset color when product changes
                                                static::updateTotal($get, $set);
                                            }
                                        }
                                    }),
                                Forms\Components\Select::make('color_id')
                                    ->label('Color')
                                    ->options(function (Forms\Get $get) {
                                        $productId = $get('product_id');
                                        if (!$productId) {
                                            return [];
                                        }

                                        $product = Product::find($productId);
                                        if (!$product) {
                                            return [];
                                        }

                                        return $product->colors->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->disabled(fn(Forms\Get $get) => !$get('product_id')),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->validationMessages([
                                        'required' => 'La cantidad es requerida',
                                        'numeric' => 'Ingrese un número por favor',
                                        'min' => 'El valor mínimo es 1'
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        static::updateTotal($get, $set);
                                    }),
                                Forms\Components\TextInput::make('price')
                                    ->label('Precio por unidad')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->addActionLabel('+ Añadir producto')
                            ->reorderable(false)
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                static::updateTotal($get, $set);
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha y Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('ARS')
                    ->sortable()
                    ->description(function ($record) {
                        if ($record->coupon) {
                            return "Cupón: {$record->coupon->code} (-{$record->discount_amount} ARS)";
                        }
                        return null;
                    }),
                /*                 Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('ARS')
                    ->sortable()
                    ->visibleFrom('lg')
                    ->description(function ($record) {
                        if ($record->coupon) {
                            return "Con descuento del {$record->coupon->discount_percentage}%";
                        }
                        return null;
                    }), */
                Tables\Columns\TextColumn::make('coupon.code')
                    ->label('Cupón')
                    ->badge()
                    ->color('success')
                    ->visibleFrom('md')
                    ->placeholder('Sin cupón'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'viewed' => 'warning',
                        'paid' => 'info',
                        'shipped' => 'success',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'viewed' => 'Visto',
                        'paid' => 'Pagado',
                        'shipped' => 'Enviado',
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado del pedido')
                    ->options([
                        'pending' => 'Pendiente',
                        'processing' => 'En proceso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_at')
                            ->label('Fecha del pedido')
                            ->native(false)
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_at'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn(Order $record): View => view(
                        'filament.resources.order-resource.pages.view-order',
                        ['order' => $record]
                    ))
                    ->modalWidth('3xl')
                    ->modalHeading(fn(Order $record) => "Pedido #{$record->id}")
                    ->modalSubmitAction(false)
                    ->modalCancelAction()
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('markAsShipped')
                            ->label('Marcar como Enviado')
                            ->icon('heroicon-o-truck')
                            ->color('success')
                            ->requiresConfirmation()
                            ->action(function (Order $record) {
                                $record->update(['status' => 'shipped']);
                                $record->updateStock('decrease');
                                Notification::make()
                                    ->title('Pedido marcado como enviado')
                                    ->body('El stock ha sido actualizado correctamente')
                                    ->success()
                                    ->send();
                            })
                            ->visible(fn(Order $record) => $record->status !== 'shipped'),
                    ])->button()->hiddenLabel()->size(ActionSize::Medium)->color('info'),
                Tables\Actions\EditAction::make()->button()->hiddenLabel()->size(ActionSize::Medium),
                Tables\Actions\DeleteAction::make()->button()->hiddenLabel()->size(ActionSize::Medium),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    protected static function updateTotal(Forms\Get $get, Forms\Set $set): void
    {
        $items = $get('items');
        $couponId = $get('coupon_id');
        $subtotal = 0;

        if ($items) {
            foreach ($items as $item) {
                if (isset($item['product_id'])) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $quantity = $item['quantity'] ?? 1;
                        $subtotal += $product->price * $quantity;
                    }
                }
            }
        }

        // Calcular descuento si hay cupón
        $discountAmount = 0;
        if ($couponId) {
            $coupon = \App\Models\Coupon::find($couponId);
            if ($coupon && $coupon->is_active) {
                $discountAmount = $coupon->calculateDiscount($subtotal);
            }
        }

        $total = $subtotal - $discountAmount;

        $set('subtotal', $subtotal);
        $set('discount_amount', $discountAmount);
        $set('total', $total);
    }
}
