<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'metadata',
        'token_count',
        'model_used',
        'response_time',
        'is_edited',
        'edited_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'token_count' => 'integer',
        'response_time' => 'decimal:3',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Message roles
    const ROLE_USER = 'user';
    const ROLE_ASSISTANT = 'assistant';
    const ROLE_SYSTEM = 'system';

    /**
     * Get the conversation that owns the message
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who sent the message (through conversation)
     */
    public function user()
    {
        return $this->hasOneThrough(User::class, Conversation::class, 'id', 'id', 'conversation_id', 'user_id');
    }

    /**
     * Scope to get only user messages
     */
    public function scopeUserMessages($query)
    {
        return $query->where('role', self::ROLE_USER);
    }

    /**
     * Scope to get only assistant messages
     */
    public function scopeAssistantMessages($query)
    {
        return $query->where('role', self::ROLE_ASSISTANT);
    }

    /**
     * Scope to get messages for a specific conversation
     */
    public function scopeForConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * Check if the message is from user
     */
    public function isUserMessage()
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Check if the message is from assistant
     */
    public function isAssistantMessage()
    {
        return $this->role === self::ROLE_ASSISTANT;
    }

    /**
     * Get formatted content with line breaks
     */
    public function getFormattedContentAttribute()
    {
        return nl2br(e($this->content));
    }

    /**
     * Check if the message has been edited
     */
    public function isEdited()
    {
        return $this->is_edited;
    }

    /**
     * Mark message as edited
     */
    public function markAsEdited()
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Get the word count of the message
     */
    public function getWordCountAttribute()
    {
        return str_word_count(strip_tags($this->content));
    }

    /**
     * Get the character count of the message
     */
    public function getCharacterCountAttribute()
    {
        return strlen($this->content);
    }

    /**
     * Get formatted response time in milliseconds
     */
    public function getFormattedResponseTimeAttribute()
    {
        return $this->response_time ? number_format($this->response_time, 0) . 'ms' : null;
    }

    /**
     * Boot method to automatically update conversation stats
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            $message->conversation->updateMessageStats();
        });

        static::deleted(function ($message) {
            $message->conversation->updateMessageStats();
        });
    }
}
