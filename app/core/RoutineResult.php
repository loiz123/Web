<?php
/** Buffered procedure result; no open CALL cursor is retained on the shared connection. */
final class RoutineResult
{
    private int $position = 0;
    public function __construct(private array $rows) {}
    public function fetch(int $mode = PDO::FETCH_ASSOC): array|false
    {
        $row = $this->rows[$this->position++] ?? false;
        return $row !== false && $mode === PDO::FETCH_NUM ? array_values($row) : $row;
    }
    public function fetchColumn(int $column = 0): mixed
    {
        $row = $this->fetch();
        return $row === false ? false : (array_values($row)[$column] ?? false);
    }
    public function fetchAll(int $mode = PDO::FETCH_ASSOC): array
    {
        $rows = array_slice($this->rows, $this->position);
        $this->position = count($this->rows);
        return $mode === PDO::FETCH_COLUMN ? array_column(array_map('array_values', $rows), 0) : $rows;
    }
    public function rowCount(): int { return (int)($this->rows[0]['__affected'] ?? count($this->rows)); }
}
