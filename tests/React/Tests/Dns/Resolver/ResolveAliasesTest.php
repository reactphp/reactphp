<?php

namespace React\Tests\Dns\Resolver;

use React\Dns\Resolver\Resolver;
use React\Dns\Query\Query;
use React\Dns\Model\Message;
use React\Dns\Model\Record;

class ResolveAliasesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers React\Dns\Resolver\Resolver::resolveAliases
     * @dataProvider provideAliasedAnswers
     */
    public function testResolveAliases(array $expectedAnswers, array $answers, $name)
    {
        $executor = $this->createExecutorMock();
        $resolver = new Resolver('8.8.8.8:53', $executor);

        $answers = $resolver->resolveAliases($answers, $name);

        $this->assertEquals($expectedAnswers, $answers);
    }

    public function provideAliasedAnswers()
    {
        return array(
            array(
                array('178.79.169.131'),
                array(
                    new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'),
                ),
                'igor.io',
            ),
            array(
                array('178.79.169.131', '178.79.169.132', '178.79.169.133'),
                array(
                    new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'),
                    new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.132'),
                    new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.133'),
                ),
                'igor.io',
            ),
            array(
                array('178.79.169.131'),
                array(
                    new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'),
                    new Record('foo.igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'),
                    new Record('bar.igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'),
                ),
                'igor.io',
            ),
            array(
                array(),
                array(
                    new Record('foo.igor.io', Message::TYPE_A, Message::CLASS_IN),
                    new Record('bar.igor.io', Message::TYPE_A, Message::CLASS_IN),
                ),
                'igor.io',
            ),
            array(
                array('178.79.169.131'),
                array(
                    new Record('igor.io', Message::TYPE_CNAME, Message::CLASS_IN, 3600, 'foo.igor.io'),
                    new Record('foo.igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'),
                ),
                'igor.io',
            ),
            array(
                array('178.79.169.131'),
                array(
                    new Record('igor.io', Message::TYPE_CNAME, Message::CLASS_IN, 3600, 'foo.igor.io'),
                    new Record('foo.igor.io', Message::TYPE_CNAME, Message::CLASS_IN, 3600, 'bar.igor.io'),
                    new Record('bar.igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'),
                ),
                'igor.io',
            ),
            array(
                array('178.79.169.131', '178.79.169.132', '178.79.169.133'),
                array(
                    new Record('igor.io', Message::TYPE_CNAME, Message::CLASS_IN, 3600, 'foo.igor.io'),
                    new Record('foo.igor.io', Message::TYPE_CNAME, Message::CLASS_IN, 3600, 'bar.igor.io'),
                    new Record('bar.igor.io', Message::TYPE_CNAME, Message::CLASS_IN, 3600, 'baz.igor.io'),
                    new Record('bar.igor.io', Message::TYPE_CNAME, Message::CLASS_IN, 3600, 'qux.igor.io'),
                    new Record('baz.igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'),
                    new Record('baz.igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.132'),
                    new Record('qux.igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.133'),
                ),
                'igor.io',
            ),
        );
    }

    private function createExecutorMock()
    {
        return $this->getMock('React\Dns\Query\ExecutorInterface');
    }
}
