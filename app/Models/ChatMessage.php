<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'operatore_id', 'messaggio', 'canale', 'hidden_for',
        'audio_path', 'audio_durata_sec',
        'attachment_path', 'attachment_name', 'attachment_size', 'attachment_mime',
        'is_pinned',
    ];

    protected $casts = [
        'hidden_for' => 'array',
        'is_pinned' => 'boolean',
    ];

    public function operatore()
    {
        return $this->belongsTo(Operatore::class);
    }

    public function letture()
    {
        return $this->hasMany(ChatMessageLettura::class, 'chat_message_id');
    }

    public function isHiddenFor(?int $operatoreId): bool
    {
        if (!$operatoreId) return false;
        $hidden = $this->hidden_for ?? [];
        return in_array($operatoreId, $hidden);
    }
}
