<?php
namespace App\Models;

use App\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasUuidKey;

    protected $fillable = ['session_id', 'student_id', 'status'];

    public function session()       { return $this->belongsTo(Session::class); }
    public function student()       { return $this->belongsTo(User::class, 'student_id'); }
    public function justification() { return $this->hasOne(Justification::class); }
    public function isPresent(): bool { return $this->status === 'present'; }
}