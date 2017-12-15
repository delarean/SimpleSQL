<?php

class SimpleSQL extends PDO
{
    private $sql;

    public function __construct($file = 'my_setting.ini')
    {
        if (!$settings = parse_ini_file($file, TRUE)) throw new exception('Unable to open ' . $file . '.');

        $dns = $settings['database']['driver'] .
            ':host=' . $settings['database']['host'] .
            ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') .
            ';dbname=' . $settings['database']['db_name'];

        parent::__construct($dns, $settings['database']['username'], $settings['database']['password']);
    }

    //формирует запрос для добавления в базу данных значенийx
    public function insert(string $table,array $parameters)
    {

        if (empty($parameters))
            throw new Exception('Переданы пустые массивы !');

        if(!self::is_assoc($parameters))
            throw new Exception('Передан неправильный параметр или неправильно указан тип!');

        $columns_str = '';
        $values_str = '';
        $values_i = 0;
        $columns_i = 0;

        foreach ($parameters as $column => $value) {

            if($column === '')
                throw new Exception('Передан пустой параметр!');

            if ($columns_i !== 0)
                $columns_str .= ',';

            $columns_str .= $column;
            $columns_i++;

            if ($values_i !== 0)
                $values_str .= ',';
            if($column === 'created_at' || $column === 'updated_at')
                $value = "\"".$value ."\"";

            $values_str .= $value;
            $values_i++;
        }

        $this->sql = "INSERT INTO " . $table . " (" . $columns_str . ") VALUES (" . $values_str . ");";

        return $this;

    }

    //Получение из базы данных значений
    public function select(string $table,array $fields)
    {

        if (empty($fields))
            throw new Exception('Переданы пустые массивы !');

        if(count($fields) === 1)
        {
            $this->sql = "SELECT ".$fields[0]." FROM " . $table . ";";
            return $this;
        }

        $columns_str = '';
        $i = 0;

        foreach ($fields as $column){

            if(gettype($column) !== 'string')
                throw new Exception('Передан неправильный параметр или неправильно указан тип!');

            if($i === 0)
                $columns_str .= $column;
            else $columns_str .= ','.$column;

            $i++;

        }

        $this->sql = "SELECT" . " (" . $columns_str . ") FROM " . $table . ";";


        return $this;

    }

     /*
      *функция добавляет условие выборки WHERE
     */
    public function where(string $column,string $operand,string $value)
    {

        //Обрезаем символ ; в sql строке
        $end_line_num = strpos($this->sql,';');
        $this->sql = mb_strimwidth($this->sql,0,$end_line_num);

        $this->sql .= " WHERE ".$column." ".$operand." ".$value.";";

        return $this;

    }

    public function request()
    {
        try{
            $sth = $this->prepare($this->sql);

            if (!$sth) {
                echo "\nself::errorInfo():\n";
                print_r($this->errorInfo());
                throw new Exception('Синтаксическая ошибка SQL!');
            }

            $sth->execute();
            $result = $sth->fetch(self::FETCH_ASSOC);

            return $result;

        }
        catch (PDOException $err){
            echo 'PDOException : '.$err->getMessage().' on line '.$err->getLine().' in '.$err->getFile();
        }
        catch (Exception $err){
            echo $err->getMessage().' on line '.$err->getLine().' in '.$err->getFile();
        }
    }

    /*
    *Обновляет информацию в таблице
    */

    public function update(string $table,array $parameters)
    {

        if (empty($parameters))
            throw new Exception('Переданы пустые массивы !');

        if(!self::is_assoc($parameters))
            throw new Exception('Передан неправильный параметр или неправильно указан тип!');

        $i = 0;

        $this->sql = "UPDATE " . $table . " SET ";

        foreach ($parameters as $column => $value) {

            if($column === '')
                throw new Exception('Передан пустой параметр!');

            if($i)
            $this->sql .= ','.$column.' = '.$value;

            else
                $this->sql .= $column.' = '.$value;

            $i++;
        }

        $this->sql .= ';';

        return $this;

    }

    /**
     * Tests if an array is associative or not.
     *
     * @param array array to check
     * @return boolean
     */
    public static function is_assoc(array $array)
    {
// Keys of the array
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

}
