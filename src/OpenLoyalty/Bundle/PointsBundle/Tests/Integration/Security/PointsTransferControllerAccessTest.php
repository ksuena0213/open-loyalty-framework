<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\PointsBundle\Tests\Integration\Security;

use OpenLoyalty\Bundle\CoreBundle\Tests\Integration\BaseAccessControlTest;
use OpenLoyalty\Bundle\PointsBundle\DataFixtures\ORM\LoadAccountsWithTransfersData;

/**
 * Class PointsTransferControllerAccessTest.
 */
class PointsTransferControllerAccessTest extends BaseAccessControlTest
{
    /**
     * @test
     */
    public function only_admin_and_seller_should_have_access_to_all_points_transfer_list()
    {
        $clients = [
            ['client' => $this->getCustomerClient(), 'status' => 403, 'name' => 'customer'],
            ['client' => $this->getSellerClient(), 'not_status' => 403, 'name' => 'seller'],
            ['client' => $this->getAdminClient(), 'not_status' => 403, 'name' => 'admin'],
        ];

        $this->checkClients($clients, '/api/points/transfer');
    }

    /**
     * @test
     */
    public function only_admin_or_seller_can_add_points(): void
    {
        $clients = [
            ['client' => $this->getCustomerClient(), 'status' => 403, 'name' => 'customer'],
            ['client' => $this->getSellerClient(), 'not_status' => 403, 'name' => 'seller'],
            ['client' => $this->getAdminClient(), 'not_status' => 403, 'name' => 'admin'],
        ];

        $this->checkClients($clients, '/api/points/transfer/add', [], 'POST');
    }

    /**
     * @test
     */
    public function it_admin_can_spend_points(): void
    {
        $clients = [
            ['client' => $this->getCustomerClient(), 'status' => 403, 'name' => 'customer'],
            ['client' => $this->getAdminClient(), 'not_status' => 403, 'name' => 'admin'],
        ];

        $this->checkClients($clients, '/api/points/transfer/spend', [], 'POST');
    }

    /**
     * @test
     */
    public function it_point_can_be_spend_by_seller_when_allow_spend_point_is_true(): void
    {
        $clients = [
            ['client' => $this->getSellerClient(), 'not_status' => 403, 'name' => 'seller'],
        ];

        $this->checkClients($clients, '/api/points/transfer/spend', [], 'POST');
    }

    /**
     * @test
     */
    public function only_admin_can_cancel_points_transfer(): void
    {
        $clients = [
            ['client' => $this->getCustomerClient(), 'status' => 403, 'name' => 'customer'],
            ['client' => $this->getSellerClient(), 'status' => 403, 'name' => 'seller'],
            ['client' => $this->getAdminClient(), 'not_status' => 403, 'name' => 'admin'],
        ];

        $this->checkClients(
            $clients,
            '/api/points/transfer/'.LoadAccountsWithTransfersData::POINTS2_ID.'/cancel',
            [],
            'POST'
        );
    }
}
