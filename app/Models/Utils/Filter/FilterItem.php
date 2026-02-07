<?php

namespace App\Models\Utils\Filter;

class FilterItem
{
    private ?string $key;
    private mixed $value;
    private ?string $operator;
    private string $logicalOperator;  // AND u OR - cómo se conecta con el siguiente item

    /**
     * @param string|null $key Columna a filtrar
     * @param mixed $value Valor del filtro
     * @param string|null $operator Operador (=, LIKE, LIKE_ALL, LIKE_LEFT, LIKE_RIGHT, IN, >, <, etc)
     * @param string $logicalOperator AND u OR - cómo se conecta con el siguiente FilterItem
     */
    public function __construct(?string $key = null, mixed $value = null, ?string $operator = null, string $logicalOperator = 'AND')
    {
        $this->key = $key;
        $this->value = $value;
        $this->operator = $operator;
        $this->logicalOperator = strtoupper($logicalOperator) === 'OR' ? 'OR' : 'AND';
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function getLogicalOperator(): string
    {
        return $this->logicalOperator;
    }

    public function setLogicalOperator(string $logicalOperator): void
    {
        $this->logicalOperator = strtoupper($logicalOperator) === 'OR' ? 'OR' : 'AND';
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'operator' => $this->operator,
            'logicalOperator' => $this->logicalOperator
        ];
    }
}
