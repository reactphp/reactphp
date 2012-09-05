<?php

namespace React\Tests\Dns\Resolver;

use React\Dns\Resolver\Resolver;
use React\Dns\Resolver\Query;
use React\Dns\Model\Message;
use React\Dns\Model\Record;

class PickRandomAnswerOfTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providePickRandomAnswerOfType
     * @covers React\Dns\Resolver\Resolver::pickRandomAnswerOfType
     */
    public function testPickRandomAnswerOfType($expectedAnswerIndexes, Message $message, $type)
    {
        $executor = $this->createExecutorMock();
        $resolver = new Resolver('8.8.8.8:53', $executor);

        foreach ($expectedAnswerIndexes as $index) {
            $expectedAnswer = $message->answers[$index];
            $success = $this->tryToMatchPickRandomAnswerOfType($resolver, $expectedAnswer, $message, $type);
            $this->assertTrue($success);
        }
    }

    public function providePickRandomAnswerOfType()
    {
        return array(
            array(
                array(0, 1),
                $this->createMessage(
                    array('qr' => 1),
                    array(
                        new Record('igor.io', Message::TYPE_TXT, Message::CLASS_IN)
                    ),
                    array(
                        new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'),
                        new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.132'),
                        new Record('igor.io', Message::TYPE_TXT, Message::CLASS_IN, 3600, 'foobar'),
                    )
                ),
                Message::TYPE_A,
            ),

            array(
                array(2),
                $this->createMessage(
                    array('qr' => 1),
                    array(
                        new Record('igor.io', Message::TYPE_A, Message::CLASS_IN)
                    ),
                    array(
                        new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131'),
                        new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.132'),
                        new Record('igor.io', Message::TYPE_TXT, Message::CLASS_IN, 3600, 'foobar'),
                    )
                ),
                Message::TYPE_TXT,
            ),
        );
    }

    private function tryToMatchPickRandomAnswerOfType(Resolver $resolver, Record $expectedAnswer, Message $message, $type)
    {
        foreach (range(1, 100) as $try) {
            $answer = $resolver->pickRandomAnswerOfType($message, $type);
            if ($expectedAnswer === $answer) {
                return true;
            }
        }

        return false;
    }

    private function createMessage(array $headerOptions, $questions, $answers = array())
    {
        $message = new Message();

        foreach ($headerOptions as $name => $value) {
            $message->header->set($name, $value);
        }

        foreach ($questions as $question) {
            $message->questions[] = $question;
        }

        foreach ($answers as $answer) {
            $message->answers[] = $answer;
        }

        $message->prepare();

        return $message;
    }

    private function createExecutorMock()
    {
        return $this->getMock('React\Dns\Query\ExecutorInterface');
    }
}
