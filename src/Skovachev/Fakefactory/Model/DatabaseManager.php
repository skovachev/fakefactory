<?php namespace Skovachev\Fakefactory\Model;

use App;
use Illuminate\Database\DatabaseManager as IlluminateDatabaseManager;

class DatabaseManager
{

    public function __construct(IlluminateDatabaseManager $db = null)
    {
        $db = $db ?: App::make('db');
        $schema = $db->getDoctrineSchemaManager();
        $this->schema = $schema;
    }

    public function registerTypeMapping($type, $mapping)
    {
        $this->schema->getDatabasePlatform()->registerDoctrineTypeMapping($type, $mapping);
    }

    public function listTableColumnsAsArray($tableName)
    {
        $columns = $this->schema->listTableDetails($tableName)->getColumns();

        $fields = array();
        foreach ($columns as $column) {
            $fields[$column->getName()] = $column->getType()->getName();
        }

        return $fields;
    }
}