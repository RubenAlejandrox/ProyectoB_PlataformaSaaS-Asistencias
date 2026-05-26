<?php
namespace App\Models;

use App\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasUuidKey;

    protected $fillable = [
        'classroom_id', 'student_id', 'enrolled_at', 'is_active',
    ];

    protected function casts(): array
    {
        return ['enrolled_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function classroom() { return $this->belongsTo(Classroom::class); }
    public function student()   { return $this->belongsTo(User::class, 'student_id'); }
}