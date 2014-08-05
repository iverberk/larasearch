<?php

use Iverberk\Larasearch\Introspect;

class IntrospectTest extends \Codeception\TestCase\Test
{
    /**
    * @var \UnitTester
    */
    protected $tester;

    /**
     * @var Introspect
     */
    protected $introspectHusband;

    /**
     * @var Introspect
     */
    protected $introspectWife;

    protected function _before()
    {
        $this->introspectHusband = new \Iverberk\Larasearch\Introspect(new Husband);
        $this->introspectWife = new \Iverberk\Larasearch\Introspect(new Wife);
    }

    protected function _after()
    {
    }

    public function testThatMetaDataAboutDatabaseTableColumnsAreFound()
    {
        $expectedTypes = [
            'created_at' => 'Doctrine\DBAL\Types\DateTimeType',
            'id' => 'Doctrine\DBAL\Types\IntegerType',
            'name' => 'Doctrine\DBAL\Types\StringType',
            'updated_at' => 'Doctrine\DBAL\Types\DateTimeType'
        ];

        $columns = $this->introspectHusband->getColumns();

        $this->assertCount(4, $columns, "Husband model should have four columns.");

        foreach($columns as $column)
        {
            $this->assertInstanceOf('Doctrine\DBAL\Schema\Column', $column);
            $this->assertInstanceOf($expectedTypes[$column->getName()], $column->getType());
        }
    }

    public function testThatRelatedModelsAreFound()
    {
        $expectedRelations = [
            'Wife' => [
                'instance' => 'Wife',
                'method' => 'wife',
                'related' => 'Child'
            ],
            'Child' => [
                'instance' => 'Child',
                'method' => 'children',
                'related' => 'Toy'
            ],
            'Toy' => [
                'instance' => 'Toy',
                'method' => 'toys'
            ]
        ];

        $relations = $this->introspectHusband->getRelatedModels();

        $this->assertCount(1, $relations, "Husband model should have one relation.");
        $this->assertArrayHasKey('Wife', $relations, "Husband model should have a Wife relation.");

        $checkRelations = function($name, $relation) use ($expectedRelations, &$checkRelations)
        {
            //$this->assertInstanceOf($expectedRelations[$name]['instance'], $relation['instance'], "Instance is not of expected type.");
            $this->assertEquals($expectedRelations[$name]['method'], $relation['method'], "Unexpected method found.");

            if ( ! empty($relation['related']) )
            {
                foreach($relation['related'] as $relationName => $related)
                {
                    $this->assertContains($expectedRelations[$name]['related'], $relationName, "Unexpected relation found.");

                    $checkRelations($relationName, $related);
                }
            }
        };

        $checkRelations('Wife', $relations['Wife']);
    }

    public function testThatRelatedModelsAreReturnedAsPaths()
    {
        $paths = $this->introspectHusband->getPaths();

        $this->assertCount(1, $paths, "Only one nested path should be found.");
        $this->assertContains('wife.children.toys', reset($paths), "Unexpected path found.");
    }

    public function testThatDocCommentsAreTakenIntoAccountWhenFindingRelatedModels()
    {
        $paths = $this->introspectWife->getPaths();

        $this->assertCount(1, $paths, "Only one nested path should be found.");
        $this->assertContains('children.father', reset($paths), "Unexpected path found.");
    }

}