<?php

namespace App\Repositories\Traits;

trait RelationHandlerTrait
{
    /**
     * Cargar relaciones dinámicamente
     */
    public function with(array $relations): self
    {
        $this->defaultRelations = [...$this->defaultRelations, ...$relations];
        return $this;
    }

    /**
     * Cargar relaciones con conteo
     */
    public function withCount(array $relations): self
    {
        // Esta funcionalidad solo está disponible con Eloquent
        if (!$this->useQueryBuilder) {
            $this->defaultRelations = [
                ...$this->defaultRelations,
                ...array_map(fn($r) => "{$r}Count", $relations)
            ];
        }
        return $this;
    }

    /**
     * Cargar relaciones con condiciones
     */
    public function withWhere(string $relation, callable $callback): self
    {
        if (!$this->useQueryBuilder) {
            $this->defaultRelations[$relation] = $callback;
        }
        return $this;
    }

    /**
     * Limpiar relaciones
     */
    public function withoutRelations(): self
    {
        $this->defaultRelations = [];
        return $this;
    }

    /**
     * Verificar si tiene relaciones cargadas
     */
    protected function hasRelations(): bool
    {
        return !empty($this->defaultRelations);
    }

    /**
     * Aplicar relaciones eager loading
     */
    protected function applyEagerLoading($query, array $relations = []): void
    {
        if ($this->useQueryBuilder) {
            return; // Query Builder no soporta eager loading
        }

        $relationsToLoad = !empty($relations) ? $relations : $this->defaultRelations;

        if (!empty($relationsToLoad)) {
            foreach ($relationsToLoad as $key => $value) {
                if (is_callable($value)) {
                    // Relación con condiciones
                    $query->with([$key => $value]);
                } else {
                    // Relación simple
                    $query->with($value);
                }
            }
        }
    }
}
