<?php namespace Illuminate\Database\Query\Grammars;

class FirebirdGrammar extends Grammar {
    
    public function columnize(array $columns)
    {
        return implode(', ', array_map(array($this, 'wrap'), $columns));
    }
    
    protected function compileColumns(Builder $query, $columns)
    {
        if ( ! is_null($query->aggregate)) return;

        $select = $query->distinct ? 'select distinct ' : 'select ';

        // If there is a limit on the query, but not an offset, we will add the top
        // clause to the query, which serves as a "limit" type clause within the
        // SQL Server system similar to the limit keywords available in MySQL.
        if ($query->limit > 0 && $query->offset <= 0)
        {
            $select .= 'rows '.$query->limit.' ';
        }

        return $select.$this->columnize($columns);
    }
    
}
