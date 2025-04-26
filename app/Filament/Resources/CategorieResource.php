<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategorieResource\Pages;
use App\Filament\Resources\CategorieResource\RelationManagers;
use App\Models\Categorie;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\Alignment;
use Filament\Notifications\Notification;

class CategorieResource extends Resource
{
    protected static ?string $model = Categorie::class;

    protected static ?string $modelLabel = 'categoria';
    protected static ?string $pluralModelLabel = 'categorias';
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Tienda';
    protected static ?int $navigationSort = 0;

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre: ')
                    ->required()
                    ->validationMessages([
                        'required' => 'El nombre es requerido',
                    ])
                    ->maxLength(255)
                    ->columnSpan([
                        'default' => 'full',
                        'lg' => 1,
                    ]),
                Forms\Components\FileUpload::make('urlImage')
                    ->label('Imagen: ')
                    ->image()
                    ->required()
                    ->validationMessages([
                        'required' => 'La imagen es requerida',
                    ])
                    ->columnSpan([
                        'default' => 'full',
                        'lg' => 1,
                    ]),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción: ')
                    ->columnSpan('full'),
            ]),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema(self::getFormSchema());
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
                Tables\Columns\ImageColumn::make('urlImage')
                    ->label('Imagen')
                    ->square(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Productos')
                    ->counts('products')
                    ->sortable()
                    ->alignCenter()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->hiddenLabel()->size(ActionSize::ExtraSmall)->extraAttributes(['class' => 'hidden']),
                Tables\Actions\EditAction::make()->button()->hiddenLabel()->size(ActionSize::Medium),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->size(ActionSize::Medium)
                    ->before(function ($action, $record) {
                        if ($record->products()->count() > 0) {
                            Notification::make()
                                ->danger()
                                ->title('Error al eliminar')
                                ->body('No se puede eliminar una categoría que tiene productos asociados.')
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($action, $records) {
                            foreach ($records as $record) {
                                if ($record->products()->count() > 0) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Error al eliminar')
                                        ->body('No se pueden eliminar categorías que tienen productos asociados.')
                                        ->send();

                                    $action->cancel();
                                    return;
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCategories::route('/'),
        ];
    }
}
