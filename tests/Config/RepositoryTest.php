<?php
namespace Ecfectus\Test\Config;

use Ecfectus\Config\Repository;
use Ecfectus\Config\FileLoader;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function loader()
    {
        return new FileLoader(__DIR__ . '/testfiles');
    }
    
    public function testRepositoryInitialises()
    {
        $repo = new Repository($this->loader());

        $this->assertTrue($repo->has('app'));

        $this->assertFalse($repo->has('database'));

        $this->assertTrue($repo->has('app.seconditem'));

        $this->assertFalse($repo->has('app.non_existant_item'));
    }

    public function testRepositoryCanReturnValue()
    {
        $repo = new Repository($this->loader());

        $this->assertEquals('second-production', $repo->get('app.seconditem'));
    }

    public function testItSetsAndGetsTheLoaderObject()
    {
        $loader = $this->loader();

        $repo = new Repository($loader, 'staging');

        $this->assertSame($repo->getLoader(), $loader);

        $loader = new FileLoader('../');

        $repo->setLoader($loader);

        $repo->getLoader();

        $this->assertSame($repo->getLoader(), $loader);
    }

    public function testItReturnsIsset()
    {
        $loader = $this->loader();

        $repo = new Repository($loader, 'staging');

        $this->assertTrue(isset($repo['app.firstitem']));
        $this->assertFalse(isset($repo['app.non_existant_item']));

        unset($repo['app.firstitem']);
        $this->assertFalse(isset($repo['app.firstitem']));
    }
    
    public function testSetEnvironment()
    {
        $repo = new Repository($this->loader());

        $repo->setEnvironment('testing');

        $this->assertEquals('testing', $repo->getEnvironment());
    }
    
    public function testEntireGroupCanBeUpdated()
    {
        $repo = new Repository($this->loader());

        $this->assertSame(include __DIR__ . '/testfiles' . '/app.php', $repo->get('app'));

        $new = array(
            'new' => 'value'
        );

        $repo->set('app', $new);

        $this->assertSame($new, $repo->get('app'));
    }
        
    public function testAValueInNamespaceCanBeUpdated()
    {
        $repo = new Repository($this->loader());

        $this->assertEquals('second-production', $repo->get('app.seconditem'));

        $repo->set('app.seconditem', 'new-value');

        $this->assertEquals('new-value', $repo->get('app.seconditem'));

        $repo->set('app.sub.item', 'new-sub-value');

        $this->assertEquals('new-sub-value', $repo->get('app.sub.item'));
    }
    
    public function testDynamicArrayAccessors()
    {
        $repo = new Repository($this->loader());

        $this->assertSame(include __DIR__ . '/testfiles' . '/app.php', $repo->get('app'));

        $new = array(
            'new' => 'value'
        );

        $repo['app'] = $new;

        $this->assertSame($new, $repo->get('app'));

        $this->assertSame($new, $repo['app']);
    }
}
