<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'avatar',
        'is_premium',
        'is_admin',
        'subscription_expires_at',
        'total_messages',
        'monthly_message_count',
        'last_activity_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'avatar_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_premium' => 'boolean',
            'is_admin' => 'boolean',
            'subscription_expires_at' => 'datetime',
            'total_messages' => 'integer',
            'monthly_message_count' => 'integer',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Get the avatar URL attribute
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        
        // Return a default avatar or gravatar
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    // User types
    const TYPE_FREE = 'free';
    const TYPE_PREMIUM = 'premium';

    // Message limits
    const FREE_MESSAGE_LIMIT = 10;
    const PREMIUM_MESSAGE_LIMIT = 1000;

    /**
     * Get the conversations for the user
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class)->orderBy('updated_at', 'desc');
    }

    /**
     * Get all messages through conversations
     */
    public function messages()
    {
        return $this->hasManyThrough(
            Message::class,
            Conversation::class,
            'user_id',         // Foreign key on conversations table
            'conversation_id', // Foreign key on messages table
            'id',              // Local key on users table
            'id'               // Local key on conversations table
        );
    }

    /**
     * Get the ratings submitted by the user
     */
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Get the user's latest conversation
     */
    public function latestConversation()
    {
        return $this->hasOne(Conversation::class)->latestOfMany();
    }

    /**
     * Get active conversations (not archived)
     */
    public function activeConversations()
    {
        return $this->conversations()->where('status', '!=', 'archived');
    }

    /**
     * Check if user is premium
     */
    public function isPremium()
    {
        return $this->is_premium && 
               ($this->subscription_expires_at === null || $this->subscription_expires_at->isFuture());
    }

    /**
     * Check if user has reached message limit
     */
    public function hasReachedMessageLimit()
    {
        $limit = $this->isPremium() ? self::PREMIUM_MESSAGE_LIMIT : self::FREE_MESSAGE_LIMIT;
        return $this->monthly_message_count >= $limit;
    }

    /**
     * Get remaining messages for the current month
     */
    public function getRemainingMessagesAttribute()
    {
        $limit = $this->isPremium() ? self::PREMIUM_MESSAGE_LIMIT : self::FREE_MESSAGE_LIMIT;
        return max(0, $limit - $this->monthly_message_count);
    }

    /**
     * Get user's message limit
     */
    public function getMessageLimitAttribute()
    {
        return $this->isPremium() ? self::PREMIUM_MESSAGE_LIMIT : self::FREE_MESSAGE_LIMIT;
    }

    /**
     * Increment message count
     */
    public function incrementMessageCount()
    {
        $this->increment('total_messages');
        $this->increment('monthly_message_count');
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Reset monthly message count (call this monthly)
     */
    public function resetMonthlyMessageCount()
    {
        $this->update(['monthly_message_count' => 0]);
    }

    /**
     * Create a new conversation for the user
     */
    public function createConversation($title = null)
    {
        return $this->conversations()->create([
            'title' => $title ?? 'New Conversation',
            'status' => 'active',
        ]);
    }

    /**
     * Get user's conversation count
     */
    public function getConversationCountAttribute()
    {
        return $this->conversations()->count();
    }

    /**
     * Get user's message count this month
     */
    public function getThisMonthMessageCountAttribute()
    {
        return $this->messages()
            ->whereMonth('messages.created_at', now()->month)
            ->whereYear('messages.created_at', now()->year)
            ->count();
    }

    /**
     * Scope for premium users
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true)
                    ->where(function ($q) {
                        $q->whereNull('subscription_expires_at')
                          ->orWhere('subscription_expires_at', '>', now());
                    });
    }

    /**
     * Scope for free users
     */
    public function scopeFree($query)
    {
        return $query->where('is_premium', false)
                    ->orWhere('subscription_expires_at', '<=', now());
    }

    /**
     * Scope for active users (recently active)
     */
    public function scopeActive($query, $days = 30)
    {
        return $query->where('last_activity_at', '>=', now()->subDays($days));
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    /**
     * Get user type attribute
     */
    public function getUserTypeAttribute()
    {
        return $this->isPremium() ? self::TYPE_PREMIUM : self::TYPE_FREE;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->is_admin ?? false;
    }

    /**
     * Scope for admin users
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * Scope for non-admin users
     */
    public function scopeNonAdmins($query)
    {
        return $query->where('is_admin', false);
    }

    /**
     * Get conversation count (method version)
     */
    public function getConversationCount()
    {
        return $this->conversations()->count();
    }

    /**
     * Get total message count
     */
    public function getTotalMessageCount()
    {
        return $this->messages()->count();
    }

    /**
     * Get messages sent today
     */
    public function getMessagesToday()
    {
        return $this->messages()
            ->whereDate('messages.created_at', now()->format('Y-m-d'))
            ->count();
    }
}
