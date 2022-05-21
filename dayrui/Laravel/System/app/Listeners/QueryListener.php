<?php

namespace App\Listeners;

use Illuminate\Database\Events\QueryExecuted;

class QueryListener
{
    // 记录查询的sql
    public function handle(QueryExecuted $event)
    {
        $sql = str_replace("?", "'%s'", $event->sql);
        $sql = vsprintf($sql, $event->bindings);

        \Phpcmf\Service::M()->db->setLastQuery($sql);
    }
}