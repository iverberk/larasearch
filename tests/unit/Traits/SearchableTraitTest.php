<?php

class nonEloquentClass {

    use \Iverberk\Larasearch\Traits\SearchableTrait;

}

class SearchableTraitTest extends \Codeception\TestCase\Test
{
   /**
    * @var \UnitTester
    */
    protected $tester;

    /**
     * @var Mockery\Mock
     */
    protected $proxyMock;

    /**
     * @var Mockery\Mock
     */
    protected $appMock;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testThatASingleProxyIsCreated()
    {
        $proxy1 = Husband::getProxy();
        $proxy2 = Husband::getProxy();

        $this->assertInstanceOf('Iverberk\Larasearch\Proxy', $proxy1);
        $this->assertInstanceOf('Iverberk\Larasearch\Proxy', $proxy2);
        $this->assertEquals($proxy1, $proxy2);
    }

    public function testThatAnExceptionIsThrownWhenSearchableIsIncludedInANonEloquentModel()
    {
        $this->setExpectedException('Exception');
        nonEloquentClass::getProxy();
    }

    public function testThatDynamicCallsAreForwardedToTheProxy()
    {
        $husband = new Husband;

        $husband->getConfig();

        $this->setExpectedException('BadMethodCallException');

        $husband->non_existant_method();
    }

    public function testThatDynamicStaticCallsAreForwardedToTheProxy()
    {

    }

}