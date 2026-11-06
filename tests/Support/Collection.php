<?php

namespace EllisLab\ExpressionEngine\Library\Data;

class Collection
{
    private $rows;

    /**
     * Retain database result rows.
     *
     * @param array $rows
     * @return void
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * Extract a column while preserving record order.
     *
     * @param string $column
     * @return array
     */
    public function pluck($column)
    {
        return array_column($this->rows, $column);
    }
}
