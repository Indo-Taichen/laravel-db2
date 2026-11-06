<?php

namespace Cooperl\DB2\Database\Schema\Grammars;

class DB2ExpressCGrammar extends DB2Grammar
{
    /**
     * Compile the query to determine if the given table exists.
     *
     * @param  string|null $schema
     * @param  string      $table
     *
     * @return string
     */
    public function compileTableExists($schema, $table)
    {
        return 'select * from syspublic.all_tables where table_schema = upper(?) and table_name = upper(?)';
    }

    /**
     * Compile the query to determine the list of columns.
     *
     * @return string
     */
    public function compileColumnExists()
    {
        return 'select column_name from syspublic.all_ind_columns where table_schema = upper(?) and table_name = upper(?)';
    }
}
