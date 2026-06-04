<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'import_type',
        'original_filename',
        'batch_id',
        'total_rows',
        'processed_rows',
        'failed_rows',
        'status',
        'error_summary',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'failed_rows' => 'integer',
            'error_summary' => 'array',
        ];
    }

    /**
     * Get the user who triggered this import.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
