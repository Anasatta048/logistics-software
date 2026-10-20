<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

foreach (Schema::getIndexes('loads') as $index) {
    echo $index['name'].' -> '.implode(',', $index['columns']).PHP_EOL;
}

echo '--- FKs on loads ---'.PHP_EOL;

foreach (DB::select('SHOW CREATE TABLE loads') as $row) {
    foreach (explode(',', (string) $row->{'Create Table'}) as $line) {
        if (str_contains($line, 'CONSTRAINT')) {
            echo trim($line).PHP_EOL;
        }
    }
}
