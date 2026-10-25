<?php

namespace App\Support\SchemaMigration;

class TableColumnComparison
{
    /**
     * @param  list<string>  $missingInLive
     * @param  list<string>  $extraInLive
     * @param  list<string>  $aligned
     * @param  array<string, array{expected: array<string, mixed>, actual: array<string, mixed>}>  $divergent
     */
    public function __construct(
        public readonly string $table,
        public readonly array $missingInLive = [],
        public readonly array $extraInLive = [],
        public readonly array $aligned = [],
        public readonly array $divergent = [],
        public readonly ?string $legacyReason = null,
    ) {
    }

    public function isLegacy(): bool
    {
        return $this->legacyReason !== null;
    }

    public function isFullyAligned(): bool
    {
        return ! $this->isLegacy()
            && $this->missingInLive === []
            && $this->divergent === [];
    }

    public function hasDrift(): bool
    {
        return $this->missingInLive !== []
            || $this->extraInLive !== []
            || $this->divergent !== [];
    }
}
