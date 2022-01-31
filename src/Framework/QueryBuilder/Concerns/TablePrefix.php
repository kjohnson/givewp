<?php

namespace Give\Framework\QueryBuilder\Concerns;

use Give\Framework\QueryBuilder\Clauses\RawSQL;

/**
 * @unreleased
 */
trait TablePrefix
{
    /**
     * @param  string  $table
     *
     * @return string
     */
    public static function prefixTable($table)
    {
        global $wpdb;

        $knownTables = [
            'users'    => $wpdb->users,
            'usermeta' => $wpdb->usermeta,
        ];

        if ($table instanceof RawSQL) {
            return $table->sql;
        }

        $table = preg_replace(
            sprintf('/^%s/', preg_quote($wpdb->prefix)),
            '',
            $table
        );

        if (array_key_exists($table, $knownTables)) {
            return $knownTables[ $table ];
        }

        return $wpdb->prefix . $table;
    }
}