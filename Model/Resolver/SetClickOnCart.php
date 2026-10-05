<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Resolver;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Phrase;
use UpturnStudio\GoogleFeed\Model\Management\CartClickService;
use UpturnStudio\GoogleFeed\Model\Management\ExceptionMessages;

/**
 * GraphQL resolver: attaches a Google Ads click to a cart for headless storefronts.
 *
 * Open to guests and customers, like the cart itself: knowing the masked cart ID is the credential. A cart that
 * belongs to a customer is only found for that customer.
 */
class SetClickOnCart implements ResolverInterface
{
    /**
     * @param CartClickService $cartClickService
     */
    public function __construct(
        private readonly CartClickService $cartClickService
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $input = (array) ($args['input'] ?? []);
        $customerId = $context->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER
            ? (int) $context->getUserId()
            : 0;

        try {
            $this->cartClickService->attachToMaskedCart((string) ($input['cart_id'] ?? ''), $input, $customerId);

            return true;
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(new Phrase('%1', [$e->getMessage()]), $e);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(new Phrase('%1', [ExceptionMessages::collect($e)]), $e);
        }
    }
}
