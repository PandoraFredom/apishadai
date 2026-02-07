<?php

namespace App\Models\Utils\Filter;

class FilterModel
{
    private array $filterItems;
    private ?string $name;

    public function __construct(?string $name = null, FilterItem ...$filterItems)
    {
        $this->name = $name;
        $this->filterItems = $filterItems;
    }

    public function getFilterItems(): array
    {
        return $this->filterItems;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'filterItems' => array_map(fn($item) => $item->toArray(), $this->filterItems)
        ];
    }
}
