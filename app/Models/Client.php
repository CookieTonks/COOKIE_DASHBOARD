<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Client extends Model
{
    protected $fillable = ['name', 'tax_id', 'notes', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class)->orderBy('name');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(PaymentPlan::class)->latest();
    }

    public function dues(): HasManyThrough
    {
        return $this->hasManyThrough(PaymentDue::class, PaymentPlan::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
