<?php namespace Illuminate\Database\Query\Grammars;


class FirebirdGrammar extends Grammar {
  
    public function wrapTable($table)
    {
        $table=strtoupper($table);
        
        if ($this->isExpression($table)) return $this->getValue($table);

        return $this->wrap($this->tablePrefix.$table);
    }
    
    protected function wrapValue($value)
    {
        $value=strtoupper($value);
        
        if ($value === '*') return $value;

        return '"'.str_replace('"', '""', $value).'"';
    }

    public function wrap($value)
    {
        $value=strtoupper($value);
        
        if ($this->isExpression($value)) return $this->getValue($value);

        // If the value being wrapped has a column alias we will need to separate out
        // the pieces so we can wrap each of the segments of the expression on it
        // own, and then joins them both back together with the "as" connector.
        if (strpos(strtolower($value), ' as ') !== false)
        {
            $segments = explode(' ', $value);

            return $this->wrap($segments[0]).' as '.$this->wrapValue($segments[2]);
        }

        $wrapped = array();

        $segments = explode('.', $value);

        // If the value is not an aliased table expression, we'll just wrap it like
        // normal, so if there is more than one segment, we will wrap the first
        // segments as if it was a table and the rest as just regular values.
        foreach ($segments as $key => $segment)
        {
            if ($key == 0 && count($segments) > 1)
            {
                $wrapped[] = $this->wrapTable($segment);
            }
            else
            {
                $wrapped[] = $this->wrapValue($segment);
            }
        }

        return implode('.', $wrapped);
    }
    
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
            $select .= 'first '.$query->limit.' ';
        }

        return $select.$this->columnize($columns);
    }

}
