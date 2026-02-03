<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VideoRoomStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoRoom extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'hotel_id',
        'name',
        'started_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => VideoRoomStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Hotel, $this>
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }
}
