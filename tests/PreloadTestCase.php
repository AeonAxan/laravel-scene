<?php

namespace Tests;


use Azaan\LaravelScene\SceneTransformer;
use Illuminate\Database\Eloquent\Collection;

class PreloadTestCase extends BaseTestCase
{
    public function testNotPreloadedIfNotDbCollection()
    {
        $transformer = new class extends SceneTransformer
        {
            public function getPreloadRelations()
            {
                return ['test'];
            }

            protected function getStructure()
            {
                return ['id'];
            }
        };

        $this->assertTransformation(
            $transformer,
            $this->personsArray(),
            [
                ['id' => 1],
                ['id' => 2],
            ]
        );
    }

    public function testPreloadRelationsLoaded()
    {
        $transformer = new class extends SceneTransformer
        {
            public function getPreloadRelations()
            {
                return ['test'];
            }

            protected function getStructure()
            {
                return ['id'];
            }
        };

        $this->assertTransformation(
            $transformer,
            $this->collectionLoadsExpectation(['test']),
            [
                ['id' => 1],
                ['id' => 2],
            ]
        );
    }

    public function testPreloadConditional()
    {
        $transformer = new class extends SceneTransformer
        {
            public function getPreloadRelations()
            {
                return [
                    'wrong' => false,
                    'one'   => true,
                    'two',
                ];
            }

            protected function getStructure()
            {
                return ['id'];
            }
        };

        $this->assertTransformation(
            $transformer,
            $this->collectionLoadsExpectation(['one', 'two']),
            [
                ['id' => 1],
                ['id' => 2],
            ]
        );
    }

    public function testPreloadRelatedTransformerRelations()
    {
        $childTransformer = new class extends SceneTransformer
        {
            public function getPreloadRelations()
            {
                return ['child'];
            }

            public function getStructure()
            {
                return [];
            }
        };

        $transformer = new class(['key' => $childTransformer]) extends SceneTransformer
        {
            public function getPreloadRelations()
            {
                return [
                    'key' => SceneTransformer::PRELOAD_RELATED,
                ];
            }

            protected function getStructure()
            {
                // overridden
                return [];
            }
        };

        $this->assertTransformation(
            $transformer,

            // transformer should intelligently preload key.child
            $this->collectionLoadsExpectation(['key.child']),

            [
                ['key' => []],
                ['key' => []],
            ]
        );
    }

    protected function collectionLoadsExpectation($expectedArgs)
    {
        $mockCollection = $this->getMockBuilder(Collection::class)
            ->setConstructorArgs([$this->personsArray()])
            ->setMethods(['loadMissing'])
            ->getMock();

        $mockCollection
            ->expects($this->once())
            ->method('loadMissing')
            ->with($expectedArgs);

        return $mockCollection;
    }
}