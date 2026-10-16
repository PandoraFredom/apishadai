<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class SyncChange extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_CREATE = 'CREATE';

    public const ACTION_UPDATE = 'UPDATE';

    public const ACTION_DELETE = 'DELETE';

    public const ACTIONS = [self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE];

    protected $table = 'sync_changes';

    protected $fillable = [
        'uuid', 'entity', 'entity_id', 'action', 'version', 'schema_version', 'payload', 'created_at', 'stock_ids', 'removed_stock_ids',
    ];

    protected $attributes = ['schema_version' => 1];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'entity_id' => 'integer',
            'version' => 'integer',
            'schema_version' => 'integer',
            'payload' => 'array',
            'stock_ids' => 'array',
            'removed_stock_ids' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $change): void {
            if (! in_array($change->action, self::ACTIONS, true)) {
                throw new InvalidArgumentException('Invalid synchronization action.');
            }

            foreach (['version', 'schema_version'] as $field) {
                $value = $change->getAttributes()[$field] ?? null;
                if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                    throw new InvalidArgumentException("{$field} must be a positive integer.");
                }
            }

            $change->uuid ??= (string) Str::uuid();
        });
    }

    protected function performUpdate(Builder $query)
    {
        throw new LogicException('Published synchronization changes cannot be updated.');
    }

    protected function performDeleteOnModel()
    {
        throw new LogicException('Published synchronization changes can only be deleted by the retention process.');
    }

    protected function incrementOrDecrement($column, $amount, $extra, $method)
    {
        throw new LogicException('Published synchronization changes cannot be updated.');
    }
}
