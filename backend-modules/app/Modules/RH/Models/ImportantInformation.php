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

    /**
     * Relation avec un seul fichier (legacy, pour compatibilité)
     */
    public function file()
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Relation avec plusieurs fichiers (nouvelle fonctionnalité)
     */
    public function files()
    {
        return $this->belongsToMany(File::class, 'important_information_file')
            ->withTimestamps()
            ->withPivot('order')
            ->orderBy('important_information_file.order');
    }

    /**
     * Récupérer tous les fichiers (file_id + files relation)
     */
    public function getAllFiles()
    {
        $allFiles = collect();
        
        // Ajouter le fichier principal si existe
        if ($this->file_id && $this->file) {
            $allFiles->push($this->file);
        }
        
        // Ajouter les fichiers additionnels
        $allFiles = $allFiles->merge($this->files);
        
        return $allFiles->unique('id');
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
