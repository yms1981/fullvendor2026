<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Issuing entity for invoices (table issuing_entity).
 *
 * @property int $entity_id
 * @property string|null $entity_name
 * @property string|null $entity_address
 * @property string|null $entity_country
 * @property string|null $entity_state
 * @property string|null $entity_city
 */
class EntidadEmisora extends Model
{
	public $timestamps = true;

	const CREATED_AT = 'created_at';

	const UPDATED_AT = 'updated_at';

	protected $table = 'issuing_entity';

	protected $primaryKey = 'entity_id';

	public $incrementing = true;

	protected $fillable = [
		'entity_name',
		'entity_address',
		'entity_country',
		'entity_state',
		'entity_city',
	];

	protected function casts(): array
	{
		return [
			'entity_id' => 'integer',
			'created_at' => 'datetime',
			'updated_at' => 'datetime',
		];
	}

	public function invoices(): HasMany
	{
		return $this->hasMany(Invoice::class, 'entidad_id', 'entity_id');
	}
}
