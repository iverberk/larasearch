<?php namespace Iverberk\Larasearch;


class UtilsTest extends \PHPUnit_Framework_TestCase {

    public function testThatKeysCanBeFoundInAnArray()
    {
        $params = [
            'test_key' => 'test_value'
        ];

        $this->assertEquals('test_value', Utils::findKey($params, 'test_key'));
        $this->assertEquals('test_value', Utils::findKey((object) $params, 'test_key'));

        $this->assertEquals('default_value', Utils::findKey($params, 'bad_key', 'default_value'));
        $this->assertEquals('default_value', Utils::findKey((object) $params, 'bad_key', 'default_value'));

        $this->assertEquals(null, Utils::findKey($params, 'bad_key'));
        $this->assertEquals(null, Utils::findKey((object) $params, 'bad_key'));
    }

    public function testThatArraysAreMergedRecursivelyByOverwritingCommonKeys()
    {
        $arr1 = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'key4' => 'value4',
                'key5' => 'value5',
                'key6' => [
                    'key7' => 'value7'
                ]
            ]
        ];

        $arr2 = [
            'key1' => 'value_override_1',
            'key8' => 'value8',
            'key3' => [
                'key4' => 'value_override_4',
                'key9' => 'value9',
                'key10' => [
                    'key11' => 'value12'
                ]
            ]
        ];

        $arr = Utils::array_merge_recursive_distinct($arr1, $arr2);

        $this->assertArrayHasKey('key1', $arr);
        $this->assertArrayHasKey('key2', $arr);
        $this->assertArrayHasKey('key8', $arr);

        $this->assertEquals('value_override_1', $arr['key1']);
        $this->assertEquals('value_override_4', $arr['key3']['key4']);
    }

    public function testThatSearchableModelsAreFoundInDirectories()
    {
        $models = Utils::findSearchableModels(array(__DIR__ . '/../../Support/Stubs'));

        $this->assertContains('Husband', $models);
        $this->assertContains('Wife', $models);
        $this->assertContains('Toy', $models);
        $this->assertContains('Child', $models);
        $this->assertContains('House\\Item', $models);
    }

}
