<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Test\Unit\Model\Management;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use UpturnStudio\GoogleFeed\Model\Management\ExceptionMessages;

/**
 * @covers \UpturnStudio\GoogleFeed\Model\Management\ExceptionMessages
 */
class ExceptionMessagesTest extends TestCase
{
    public function testAPlainExceptionKeepsItsMessage(): void
    {
        $this->assertSame('It broke.', ExceptionMessages::collect(new \RuntimeException('It broke.')));
        $this->assertSame('Nope.', ExceptionMessages::collect(new LocalizedException(new Phrase('Nope.'))));
    }

    public function testASingleAddedErrorIsReadFromTheMessage(): void
    {
        $e = new InputException(new Phrase('The feed is not valid.'));
        $e->addError(new Phrase('%1', ['A feed name is required.']));

        $this->assertSame('A feed name is required.', ExceptionMessages::collect($e));
    }

    public function testSeveralAddedErrorsAreJoined(): void
    {
        $e = new InputException(new Phrase('The feed is not valid.'));
        $e->addError(new Phrase('%1', ['A feed name is required.']));
        $e->addError(new Phrase('%1', ['store_id 9 is not a store view.']));
        $e->addError(new Phrase('%1', ['Visibility 7 is not valid.']));

        $this->assertSame(
            'A feed name is required. store_id 9 is not a store view. Visibility 7 is not valid.',
            ExceptionMessages::collect($e)
        );
    }
}
