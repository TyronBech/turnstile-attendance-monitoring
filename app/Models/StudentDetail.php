<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentDetail extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'usr_student_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'id_number',
        'level',
        'section',
        'guardian_name',
        'guardian_contact_number',
        'active_id_number',
    ];

    /**
     * Get the user that owns the student detail record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
