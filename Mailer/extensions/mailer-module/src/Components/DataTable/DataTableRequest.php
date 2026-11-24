<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\DataTable;

use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

final class DataTableRequest
{
    private function __construct(
        public readonly string $search,
        public readonly ?string $orderColumn,
        public readonly ?string $orderDir,
        public readonly int $start,
        public readonly int $length,
        private readonly array $columns,
    ) {
    }

    public static function fromQuery(array $query, bool $requireOrder = true): self
    {
        $columns = $query['columns'] ?? null;
        if (!is_array($columns) || !isset($query['start'], $query['length'])) {
            throw new BadRequestException('Missing DataTables parameters.', IResponse::S400_BadRequest);
        }

        $orderColumn = null;
        $orderDir = null;
        if (isset($query['order'][0])) {
            $orderColumn = $columns[$query['order'][0]['column'] ?? null]['name'] ?? null;
            $orderDir = $query['order'][0]['dir'] ?? null;
            if (!is_string($orderColumn) || !in_array(strtolower((string) $orderDir), ['asc', 'desc'], true)) {
                throw new BadRequestException('Invalid DataTables order parameters.', IResponse::S400_BadRequest);
            }
        } elseif ($requireOrder) {
            throw new BadRequestException('Missing DataTables order parameters.', IResponse::S400_BadRequest);
        }

        return new self(
            search: (string) ($query['search']['value'] ?? ''),
            orderColumn: $orderColumn,
            orderDir: $orderDir,
            start: (int) $query['start'],
            length: (int) $query['length'],
            columns: $columns,
        );
    }

    public function columnSearch(string $columnName): ?array
    {
        foreach ($this->columns as $column) {
            if (($column['name'] ?? null) === $columnName) {
                $value = $column['search']['value'] ?? '';
                return $value !== '' ? explode(',', (string) $value) : null;
            }
        }
        return null;
    }
}
