<?php

namespace UksusoFF\WebtreesModules\Photos\Helpers;

use Fisharebest\Webtrees\Tree;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

class DatabaseHelper
{
    public function getRecentPhotos(Tree $tree, int $limit): Collection
    {
        return DB::table('media_file')
            ->where('m_file', '=', $tree->id())
            ->where('old_gedcom', '=', '')
            ->rightJoin('change', function(JoinClause $join) {
                $join->on('m_id', '=', 'xref');
            })
            ->orderByDesc('media_file.id')
            ->limit($limit)
            ->get([
                'm_id',
                'change_time',
            ]);
    }
}
