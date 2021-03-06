<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\Form\Handler;

use Broadway\CommandHandling\CommandBus;
use Doctrine\ORM\EntityManager;
use OpenLoyalty\Bundle\UserBundle\Service\UserManager;
use OpenLoyalty\Component\Core\Domain\Model\Label;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerAddress;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerCompanyDetails;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerDetails;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerLoyaltyCardNumber;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Exception\EmailAlreadyExistsException;
use OpenLoyalty\Component\Customer\Domain\Exception\LoyaltyCardNumberAlreadyExistsException;
use OpenLoyalty\Component\Customer\Domain\Exception\PhoneAlreadyExistsException;
use OpenLoyalty\Component\Customer\Domain\Validator\CustomerUniqueValidator;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CustomerEditFormHandler.
 */
class CustomerEditFormHandler
{
    /**
     * @var CommandBus
     */
    protected $commandBus;
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var CustomerUniqueValidator
     */
    protected $customerUniqueValidator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * CustomerEditFormHandler constructor.
     *
     * @param CommandBus              $commandBus
     * @param UserManager             $userManager
     * @param EntityManager           $em
     * @param CustomerUniqueValidator $customerUniqueValidator
     * @param TranslatorInterface     $translator
     */
    public function __construct(
        CommandBus $commandBus,
        UserManager $userManager,
        EntityManager $em,
        CustomerUniqueValidator $customerUniqueValidator,
        TranslatorInterface $translator
    ) {
        $this->commandBus = $commandBus;
        $this->userManager = $userManager;
        $this->em = $em;
        $this->customerUniqueValidator = $customerUniqueValidator;
        $this->translator = $translator;
    }

    /**
     * @param CustomerId    $customerId
     * @param FormInterface $form
     *
     * @return bool
     */
    public function onSuccess(CustomerId $customerId, FormInterface $form): bool
    {
        $email = null;
        $customerData = $form->getData();

        if (isset($customerData['email']) && !empty($customerData['email'])) {
            $email = strtolower($customerData['email']);

            try {
                if ($this->isDifferentUserExistsWithThisEmail((string) $customerId, $email)) {
                    throw new EmailAlreadyExistsException();
                }

                $this->customerUniqueValidator->validateEmailUnique($email, $customerId);
            } catch (EmailAlreadyExistsException $e) {
                $form->get('email')->addError(
                    new FormError($this->translator->trans($e->getMessageKey(), $e->getMessageParams()))
                );
            }
        }

        if (isset($customerData['phone']) && $customerData['phone']) {
            try {
                $this->customerUniqueValidator->validatePhoneUnique($customerData['phone'], $customerId);
            } catch (PhoneAlreadyExistsException $e) {
                $form->get('phone')->addError(
                    new FormError($this->translator->trans($e->getMessageKey(), $e->getMessageParams()))
                );
            }
        }

        if (array_key_exists('phone', $customerData) && null === $customerData['phone']) {
            $customerData['phone'] = '';
        }

        if (isset($customerData['loyaltyCardNumber'])) {
            try {
                $this->customerUniqueValidator->validateLoyaltyCardNumberUnique(
                    $customerData['loyaltyCardNumber'],
                    $customerId
                );
            } catch (LoyaltyCardNumberAlreadyExistsException $e) {
                $form->get('loyaltyCardNumber')->addError(
                    new FormError($this->translator->trans($e->getMessageKey(), $e->getMessageParams()))
                );
            }
        }

        if ($form->getErrors(true)->count() > 0) {
            return false;
        }

        if (isset($customerData['company'])
            && array_key_exists('name', $customerData['company'])
            && array_key_exists('nip', $customerData['company'])
            && empty($customerData['company']['name'])
            && empty($customerData['company']['nip'])
        ) {
            // user wants to delete the company details
            // alternatively, users may send company = [] by themselves.
            $customerData['company'] = [];
        }

        $labels = array_map(function (Label $label) {
            return $label->serialize();
        }, $form->get('labels')->getData() ?? []);

        $customerData['labels'] = $labels;

        $command = new UpdateCustomerDetails($customerId, $customerData);
        $this->commandBus->dispatch($command);

        if (isset($customerData['address'])) {
            $updateAddressCommand = new UpdateCustomerAddress(
                $customerId,
                $customerData['address']
            );
            $this->commandBus->dispatch($updateAddressCommand);
        }

        if (isset($customerData['company'])) {
            $updateCompanyDataCommand = new UpdateCustomerCompanyDetails(
                $customerId,
                $customerData['company']
            );
            $this->commandBus->dispatch($updateCompanyDataCommand);
        }

        if (isset($customerData['loyaltyCardNumber'])) {
            $loyaltyCardCommand = new UpdateCustomerLoyaltyCardNumber(
                $customerId,
                $customerData['loyaltyCardNumber']
            );
            $this->commandBus->dispatch($loyaltyCardCommand);
        }

        if (empty($email)) {
            return true;
        }

        $user = $this->em->getRepository('OpenLoyaltyUserBundle:Customer')->find((string) $customerId);

        $user->setEmail($email);
        $this->userManager->updateUser($user);

        return true;
    }

    /**
     * @param string $id
     * @param string $email
     *
     * @return bool
     */
    private function isDifferentUserExistsWithThisEmail(string $id, string $email): bool
    {
        $qb = $this->em->createQueryBuilder()->select('u')->from('OpenLoyaltyUserBundle:Customer', 'u');
        $qb->andWhere('u.email = :email')->setParameter('email', $email);
        $qb->andWhere('u.id != :id')->setParameter('id', $id);

        $result = $qb->getQuery()->getResult();

        return count($result) > 0;
    }
}
