<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'name', 'color'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('organization_id', auth()->user()->organization_id);
            }
        });

        static::creating(function (Tag $tag) {
            if (auth()->check() && !$tag->organization_id) {
                $tag->organization_id = auth()->user()->organization_id;
            }
        });
    }

    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'ticket_tag');
    }
}
