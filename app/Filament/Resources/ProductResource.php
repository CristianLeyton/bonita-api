<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\ActionSize;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $modelLabel = 'producto';
    protected static ?string $pluralModelLabel = 'productos';
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Tienda';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre: ')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'required' => 'El nombre es requerido',
                        'unique' => 'El nombre ya está en uso',
                    ])
                    ->maxLength(255),

                // Reemplazar el FileUpload simple por un componente de múltiples imágenes
                Forms\Components\Repeater::make('images')
                    ->label('Imágenes')
                    ->relationship('images')
                    ->addActionLabel('Agregar imagen')
                    ->schema([
                        Forms\Components\FileUpload::make('url')
                            ->label('Imagen')
                            ->image()
                            ->required()
                            ->validationMessages([
                                'required' => 'La imagen es requerida',
                            ]),
                        Forms\Components\TextInput::make('alt_text')
                            ->label('Descripción')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Imagen principal')
                            ->default(false),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->maxItems(5)
                    ->reorderable()
                    ->orderColumn('sort_order')
                    ->collapsible()
                    ->itemLabel(function (array $state): string {
                        $url = $state['url'] ?? null;
                        if (is_array($url)) {
                            $url = $url[0] ?? null;
                        }
                        $isPrimary = $state['is_primary'] ?? false;
                        $label = $state['alt_text'] ?? 'Nueva imagen';
                        return $isPrimary ? "⭐ {$label}" : $label;
                    })
                    ->validationMessages([
                        'min' => 'Debes agregar al menos una imagen',
                        'max' => 'No puedes agregar más de 5 imágenes'
                    ]),

                Forms\Components\TextInput::make('sku')
                    ->label('SKU: ')
                    ->unique(Product::class, 'sku', ignoreRecord: true)
                    ->required()
                    ->numeric()
                    ->default(fn() => (string)(Product::max('id') ?? 0) + 1)
                    ->maxLength(20)
                    ->validationMessages([
                        'required' => 'El codigo SKU es requerido',
                        'unique' => 'El codigo SKU ya existe',
                        'max_digits' => 'El codigo no puede superar los 20 digitos',
                    ]),
                Forms\Components\TextInput::make('price')
                    ->label('Precio: ')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('ARS $')
                    ->validationMessages([
                        'required' => 'El precio es requerido',
                        'numeric' => 'El precio debe ser un numero',
                        'min' => 'La cantidad no puede ser menor a cero',
                    ]),
                Forms\Components\TextInput::make('quantity')
                    ->label('Cantidad: ')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->default(0)
                    ->validationMessages([
                        'numeric' => 'La cantidad debe ser un numero',
                        'min' => 'La cantidad no puede ser menor a cero'
                    ]),
                Forms\Components\Select::make('categorie_id')
                    ->label('Categoría: ')
                    ->relationship('categorie', 'name')
                    ->required()
                    ->validationMessages([
                        'required' => 'Selecciona una categoria',
                    ])
                    ->createOptionModalHeading('Crear categoría')
                    ->createOptionForm(\App\Filament\Resources\CategorieResource::getFormSchema()),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción: '),
                Forms\Components\Select::make('colors')
                    ->label('Colores disponibles: ')
                    ->relationship('colors', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->createOptionModalHeading('Crear color')
                    ->createOptionForm([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre')
                                ->required(),
                            Forms\Components\ColorPicker::make('hex_code')
                                ->label('Código de color')
                                ->required(),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                // Mostrar la imagen principal
                Tables\Columns\ImageColumn::make('primaryImage.url')
                    ->label('Imagen')
                    ->square(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('Codigo SKU')
                    ->searchable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->money('ARS', locale: 'es_AR')
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->sortable()
                    ->visibleFrom('md')
                    ->color(fn($state) => match (true) {
                        $state < 5 => 'danger',   // rojo
                        $state < 10 => 'warning', // amarillo
                        default => 'success',     // verde
                    }),
                Tables\Columns\TextColumn::make('categorie.name')
                    ->label('Categoría')
                    ->numeric()
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime()
                    ->sortable()
                    ->visibleFrom('md')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de actualización')
                    ->dateTime()
                    ->sortable()
                    ->visibleFrom('md')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categorie_id')
                    ->label('Categoría')
                    ->relationship('categorie', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('colors')
                    ->label('Color')
                    ->relationship('colors', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->hiddenLabel()->size(ActionSize::ExtraSmall)->extraAttributes(['class' => 'hidden']),
                Tables\Actions\EditAction::make()->button()->hiddenLabel()->size(ActionSize::Medium),
                Tables\Actions\DeleteAction::make()->button()->hiddenLabel()->size(ActionSize::Medium),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProducts::route('/'),
        ];
    }
}
