<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, WithHeadings, WithMapping
{
    protected array $filters;
    protected string $sortField;
    protected string $sortOrder;
    protected array $columns;

    protected array $allowedColumns = [
        'id',
        'name',
        'email',
        // 'status',
    ];

    public function __construct(
        array $filters = [],
        string $sortField = 'id',
        string $sortOrder = 'desc',
        array $columns = []
    ) {
        $this->filters = $filters;
        $this->sortField = in_array($sortField, $this->allowedColumns, true) ? $sortField : 'id';
        $this->sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        $cleanColumns = array_values(array_filter($columns, fn ($c) => in_array($c, $this->allowedColumns, true)));
        $this->columns = count($cleanColumns) ? $cleanColumns : $this->allowedColumns;
    }

    public function query()
    {
        $query = User::query()->select($this->columns);

        $this->applyGlobalFilter($query);
        $this->applyColumnFilters($query);

        $query->orderBy($this->sortField, $this->sortOrder);

        return $query;
    }

    protected function applyGlobalFilter(Builder $query): void
    {
        $global = data_get($this->filters, 'global.value');

        if ($global === null || $global === '') {
            return;
        }

        $query->where(function ($q) use ($global) {
            foreach (['id', 'name', 'email'] as $field) {
                if ($field === 'id' && is_numeric($global)) {
                    $q->orWhere('id', $global);
                } else {
                    $q->orWhere($field, 'like', '%' . $global . '%');
                }
            }
        });
    }

    protected function applyColumnFilters(Builder $query): void
    {
        foreach ($this->allowedColumns as $field) {
            if ($field === 'id') {
                $value = data_get($this->filters, "{$field}.constraints.0.value");
                $matchMode = data_get($this->filters, "{$field}.constraints.0.matchMode", 'equals');

                if ($value !== null && $value !== '') {
                    $this->applyMatch($query, $field, $value, $matchMode);
                }

                continue;
            }

            $value = data_get($this->filters, "{$field}.constraints.0.value");
            $matchMode = data_get($this->filters, "{$field}.constraints.0.matchMode", 'contains');

            if ($value !== null && $value !== '') {
                $this->applyMatch($query, $field, $value, $matchMode);
            }
        }
    }

    protected function applyMatch(Builder $query, string $field, mixed $value, string $matchMode): void
    {
        switch ($matchMode) {
            case 'startsWith':
                $query->where($field, 'like', $value . '%');
                break;

            case 'endsWith':
                $query->where($field, 'like', '%' . $value);
                break;

            case 'equals':
                $query->where($field, $value);
                break;

            case 'notEquals':
                $query->where($field, '!=', $value);
                break;

            case 'contains':
            default:
                $query->where($field, 'like', '%' . $value . '%');
                break;
        }
    }

    public function headings(): array
    {
        $labels = [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            // 'status' => 'Status',
        ];

        return array_map(fn ($column) => $labels[$column] ?? ucfirst($column), $this->columns);
    }

    public function map($user): array
    {
        return array_map(function ($column) use ($user) {
            return $user->{$column};
        }, $this->columns);
    }
}