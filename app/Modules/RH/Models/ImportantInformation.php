<?php

namespace App\Modules\RH\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Stockage\Models\File;

class ImportantInformation extends Model
{
    protected $table = 'important_informations';
    
    protected $fillable = [
        'title',
        'description',
        'icon',
        'color',
        'link',
        'file_id',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('created_at', 'desc');
    }
}
