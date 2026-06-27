<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'subject',
        'description',
        'status',
        'priority',
        'requester_id',
        'assignee_id',
    ];

    protected static function booted(): void
    {
        // Global scope: tenant isolation — always filter by the authenticated user's org
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('organization_id', auth()->user()->organization_id);
            }
        });

        static::creating(function (Ticket $ticket) {
            if (auth()->check() && !$ticket->organization_id) {
                $ticket->organization_id = auth()->user()->organization_id;
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'ticket_tag');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the SLA policy applicable to this ticket based on its priority.
     */
    public function slaPolicy()
    {
        return SlaPolicy::where('organization_id', $this->organization_id)
            ->where('priority', $this->priority)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Compute SLA status: on_track, warning, breached, met, or none.
     */
    public function slaStatus(): array
    {
        $policy = $this->slaPolicy();
        if (!$policy) {
            return ['status' => 'none', 'response_due' => null, 'resolution_due' => null];
        }

        $createdAt = $this->created_at;
        $responseDue = $createdAt->copy()->addMinutes($policy->response_time_minutes);
        $resolutionDue = $createdAt->copy()->addMinutes($policy->resolution_time_minutes);
        $now = now();

        if (in_array($this->status, ['resolved', 'closed'])) {
            $wasBreached = $this->updated_at > $resolutionDue;
            return [
                'status' => $wasBreached ? 'breached' : 'met',
                'response_due' => $responseDue->toIso8601String(),
                'resolution_due' => $resolutionDue->toIso8601String(),
            ];
        }

        $resolutionBreached = $now > $resolutionDue;
        $responseBreached = $now > $responseDue;

        $warningThreshold = $resolutionDue->copy()->subMinutes((int)($policy->resolution_time_minutes * 0.2));
        $isWarning = !$resolutionBreached && $now > $warningThreshold;

        return [
            'status' => $resolutionBreached ? 'breached' : ($isWarning ? 'warning' : ($responseBreached ? 'warning' : 'on_track')),
            'response_due' => $responseDue->toIso8601String(),
            'resolution_due' => $resolutionDue->toIso8601String(),
        ];
    }
}
