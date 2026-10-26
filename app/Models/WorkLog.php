<?php

namespace App\Models;

use Database\Factories\WorkLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property int $half_hours
 * @property Carbon $worked_on
 * @property-read Project $project
 * @property-read User $user
 */
#[Fillable(['project_id', 'user_id', 'worked_on', 'half_hours', 'description'])]
class WorkLog extends Model
{
    /** @use HasFactory<WorkLogFactory> */
    use HasFactory;

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<WorkLog> $query
     * @return Builder<WorkLog>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin() ? $query : $query->where('user_id', $user->id);
    }

    protected function casts(): array
    {
        return ['worked_on' => 'date', 'half_hours' => 'integer'];
    }
}
