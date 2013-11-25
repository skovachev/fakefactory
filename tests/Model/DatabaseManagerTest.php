<?php

use Skovachev\Fakefactory\Model\DatabaseManager;

class DatabaseManagerTest extends TestCase {

    protected $schema;

    protected function getDatabaseManager()
    {
        $db = Mockery::mock('Illuminate\Database\DatabaseManager');
        $schema = Mockery::mock();

        $db->shouldReceive('getDoctrineSchemaManager')->once()->andReturn($schema);

        $this->schema = $schema;

        $manager = new DatabaseManager($db);

        return $manager;
    }

    public function testRegisterTypeMapping()
    {
        $manager = $this->getDatabaseManager();
        $type = 'foo';
        $mapping = 'bar';

        $db = Mockery::mock();
        $db->shouldReceive('registerDoctrineTypeMapping')->once()->with($type, $mapping);

        $this->schema->shouldReceive('getDatabasePlatform')->once()->andReturn($db);

        $manager->registerTypeMapping($type, $mapping);
    }

    public function testListTableColumnsAsArray()
    {
        $manager = $this->getDatabaseManager();
        $column = Mockery::mock();
        $columnType = Mockery::mock();

        $column->shouldReceive('getName')->once()->andReturn('colName');
        $column->shouldReceive('getType')->once()->andReturn($columnType);

        $columnType->shouldReceive('getName')->once()->andReturn('colType');

        $columns = array($column);

        $tableDetails = Mockery::mock();
        $tableDetails->shouldReceive('getColumns')->once()->andReturn($columns);

        $this->schema->shouldReceive('listTableDetails')->with('tableName')->once()->andReturn($tableDetails);

        $result = $manager->listTableColumnsAsArray('tableName');

        $this->assertEquals($result, array('colName' => 'colType'));
    }
    
}