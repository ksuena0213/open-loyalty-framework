<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\Service;

use OpenLoyalty\Bundle\EmailBundle\Mailer\OloyMailer;
use OpenLoyalty\Bundle\EmailBundle\Service\MessageFactoryInterface;
use OpenLoyalty\Bundle\SettingsBundle\Service\ConditionsUploader;
use OpenLoyalty\Component\Customer\Domain\ReadModel\InvitationDetails;
use OpenLoyalty\Component\Level\Domain\Level;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Bundle\UserBundle\Entity\User;
use OpenLoyalty\Component\Campaign\Domain\Model\Coupon;

/**
 * Class EmailProvider.
 */
class EmailProvider
{
    /**
     * @var MessageFactoryInterface
     */
    protected $messageFactory;

    /** @var array */
    protected $parameters = [];

    /** @var OloyMailer */
    protected $mailer;

    /**
     * @var string
     */
    protected $emailFromName;

    /**
     * @var string
     */
    protected $emailFromAddress;

    /**
     * @var string
     */
    protected $passwordResetUrl;

    /**
     * @var string
     */
    protected $loyaltyProgramName;

    /**
     * @var string
     */
    protected $ecommerceAddress;

    /**
     * @var string
     */
    protected $customerPanelUrl;

    /**
     * @var string
     */
    protected $invitationUrl;

    /**
     * EmailProvider constructor.
     *
     * @param MessageFactoryInterface $messageFactory
     * @param OloyMailer              $mailer
     * @param array                   $parameters
     */
    public function __construct(MessageFactoryInterface $messageFactory, OloyMailer $mailer, array $parameters)
    {
        $this->messageFactory = $messageFactory;
        $this->mailer = $mailer;
        $this->parameters = $parameters;
        $this->emailFromName = $parameters['from_name'] ?? '';
        $this->emailFromAddress = $parameters['from_address'] ?? '';
        $this->passwordResetUrl = $parameters['password_reset_url'] ?? '';
        $this->loyaltyProgramName = $parameters['loyalty_program_name'] ?? '';
        $this->ecommerceAddress = $parameters['ecommerce_address'] ?? '';
        $this->customerPanelUrl = $parameters['customer_panel_url'] ?? '';
        $this->invitationUrl = $parameters['frontend_invitation_url'] ?? '';
    }

    /**
     * @param string      $subject
     * @param string      $email
     * @param string|null $template
     * @param array|null  $params
     *
     * @return bool
     */
    public function sendMessage(string $subject, string $email, string $template = null, array $params = null)
    {
        $message = $this->messageFactory->create();
        $message->setSubject($subject);
        $message->setRecipientEmail($email);
        $message->setRecipientName($email);
        $message->setSenderEmail($this->emailFromAddress);
        $message->setSenderName($this->emailFromName);
        $message->setTemplate($template);
        $message->setParams($params);

        return $this->mailer->send($message);
    }

    /**
     * @param CustomerDetails $registeredUser
     * @param string          $password
     *
     * @return bool
     */
    public function registrationWithTemporaryPassword(CustomerDetails $registeredUser, string $password)
    {
        return $this->sendMessage(
            'Account created',
            $registeredUser->getEmail(),
            'OpenLoyaltyUserBundle:email:registration_with_temporary_password.html.twig',
            [
                'program_name' => $this->loyaltyProgramName,
                'email' => $registeredUser->getEmail(),
                'loyalty_card_number' => $registeredUser->getLoyaltyCardNumber(),
                'phone' => $registeredUser->getPhone(),
                'password' => $password,
                'customer_panel_url' => $this->customerPanelUrl,
                'conditions_file' => $this->customerPanelUrl.'#!/'.ConditionsUploader::CONDITIONS_FILENAME,
            ]
        );
    }

    /**
     * @param InvitationDetails $invitationDetails
     *
     * @return bool
     */
    public function invitationEmail(InvitationDetails $invitationDetails): bool
    {
        return $this->sendMessage(
            'Invitation',
            $invitationDetails->getRecipientEmail(),
            'OpenLoyaltyUserBundle:email:invitation.html.twig',
            [
                'referrerName' => $invitationDetails->getReferrerName(),
                'url' => $this->customerPanelUrl.$this->invitationUrl.$invitationDetails->getToken(),
            ]
        );
    }

    /**
     * @param User        $registeredUser
     * @param string|null $url
     *
     * @return bool
     */
    public function registration(User $registeredUser, string $url = null): bool
    {
        return $this->sendMessage(
            'Account created',
            $registeredUser->getEmail(),
            'OpenLoyaltyUserBundle:email:registration.html.twig',
            [
                'username' => $registeredUser->getEmail(),
                'url' => $url,
                'conditions_file' => $this->customerPanelUrl.'#!/'.ConditionsUploader::CONDITIONS_FILENAME,
            ]
        );
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function resettingPasswordMessage(User $user)
    {
        return $this->sendMessage(
            'Password reset requested',
            $user->getEmail(),
            'OpenLoyaltyUserBundle:email:password_reset.html.twig',
            [
                'program_name' => $this->loyaltyProgramName,
                'url_reset_password' => $this->passwordResetUrl.'/'.$user->getConfirmationToken(),
            ]
        );
    }

    /**
     * @param CustomerDetails $customer
     * @param Campaign        $campaign
     * @param Coupon          $coupon
     *
     * @return bool
     */
    public function customerBoughtCampaign(CustomerDetails $customer, Campaign $campaign, Coupon $coupon)
    {
        if (!$customer->getEmail()) {
            return false;
        }
        $subject = sprintf('%s - new reward', $this->loyaltyProgramName);

        return $this->sendMessage(
            $subject,
            $customer->getEmail(),
            'OpenLoyaltyUserBundle:email:customer_reward_bought.html.twig',
            [
                'program_name' => $this->loyaltyProgramName,
                'reward_name' => $campaign->getName(),
                'reward_code' => $coupon->getCode(),
                'reward_instructions' => $campaign->getUsageInstruction(),
                'ecommerce_address' => $this->ecommerceAddress,
            ]
        );
    }

    /**
     * @param CustomerDetails $customer
     * @param float           $availableAmount
     * @param float           $pointsAdded
     *
     * @return bool
     */
    public function addPointsToCustomer(CustomerDetails $customer, float $availableAmount, float $pointsAdded)
    {
        if (!$customer->getEmail()) {
            return false;
        }
        $subject = sprintf('%s - new points', $this->loyaltyProgramName);

        return $this->sendMessage(
            $subject,
            $customer->getEmail(),
            'OpenLoyaltyUserBundle:email:new_points.html.twig',
            [
                'program_name' => $this->loyaltyProgramName,
                'added_points_amount' => $pointsAdded,
                'active_points_amount' => $availableAmount,
                'ecommerce_address' => $this->ecommerceAddress,
            ]
        );
    }

    /**
     * @param CustomerDetails $customer
     * @param Level           $level
     *
     * @return bool
     */
    public function moveToLevel(CustomerDetails $customer, Level $level)
    {
        if (!$customer->getEmail()) {
            return false;
        }

        $subject = sprintf('%s - new level', $this->loyaltyProgramName);

        return $this->sendMessage(
            $subject,
            $customer->getEmail(),
            'OpenLoyaltyUserBundle:email:new_level.html.twig',
            [
                'program_name' => $this->loyaltyProgramName,
                'level_name' => $level->getName(),
                'level_discount' => number_format($level->getReward()->getValue() * 100, 0),
                'ecommerce_address' => $this->ecommerceAddress,
            ]
        );
    }
}
