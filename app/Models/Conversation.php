<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'status',
        'summary',
        'message_count',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'message_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_DELETED = 'deleted';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the latest message in this conversation
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Get user messages only
     */
    public function userMessages()
    {
        return $this->messages()->where('role', 'user');
    }

    /**
     * Get assistant messages only
     */
    public function assistantMessages()
    {
        return $this->messages()->where('role', 'assistant');
    }

    /**
     * Update message count and last message time
     */
    public function updateMessageStats()
    {
        $this->update([
            'message_count' => $this->messages()->count(),
            'last_message_at' => $this->messages()->latest('messages.created_at')->value('messages.created_at'),
        ]);
    }

    /**
     * Check if conversation is active
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Archive the conversation
     */
    public function archive()
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    /**
     * Restore archived conversation
     */
    public function restore()
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Scope for active conversations
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for archived conversations
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }
}
