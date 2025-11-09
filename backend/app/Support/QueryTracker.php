<?php

namespace App\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class QueryTracker
{
    private ConnectionInterface $connection;
    private bool $wasLogging = false;

    public function __construct(?ConnectionInterface $connection = null)
    {
        $this->connection = $connection ?: DB::connection();
    }

    public function start(): void
    {
        $this->wasLogging = $this->connection->logging();
        $this->connection->enableQueryLog();
    }

    public function finish(): array
    {
        $queries = $this->connection->getQueryLog();
        $this->connection->flushQueryLog();

        if (! $this->wasLogging) {
            $this->connection->disableQueryLog();
        }

        return [
            'queries' => count($queries),
            'durationMs' => round(array_sum(array_column($queries, 'time')), 2),
        ];
    }
}

