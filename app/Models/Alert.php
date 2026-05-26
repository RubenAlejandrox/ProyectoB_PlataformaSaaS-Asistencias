<?php
namespace App\Models;

use App\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasUuidKey;

    protected $fillable = ['student_id', 'classroom_id', 'type', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function student()   { return $this->belongsTo(User::class, 'student_id'); }
    public function classroom() { return $this->belongsTo(Classroom::class); }
}