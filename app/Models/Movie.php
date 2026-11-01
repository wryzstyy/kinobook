<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['title', 'description', 'duration_minutes', 'age_rating', 'poster_url'])]
class Movie extends Model
{
    // В базе лежит путь файла, наружу отдаём полный URL
    protected function posterUrl(): Attribute
    {
        return Attribute::get(function (?string $value) {
            if (! $value) {
                return null;
            }

            if (str_starts_with($value, 'http')) {
                return $value; // внешняя ссылка
            }

            return Storage::disk('public')->url($value);
        });
    }

    // Удаляет файл постера с диска (если он наш, а не внешняя ссылка)
    public function deletePosterFile(): void
    {
        $path = $this->getRawOriginal('poster_url');

        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class)->withTimestamps();
    }

    public function screenings(): HasMany
    {
        return $this->hasMany(Screening::class);
    }
}