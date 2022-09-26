<?php
/**
 * 2007-2019 Friends of PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

namespace GraphicCustomManager\Repository;

use Doctrine\DBAL\Connection;
use PDO;

class CustomerRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $dbPrefix;

    /**
     * @param Connection $connection
     * @param string $dbPrefix
     */
    public function __construct(Connection $connection, $dbPrefix)
    {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
    }

    /**
     * Finds customer id if such exists.
     *
     * @param int $customerId
     *
     * @return array
     */
    public function findCustomFieldsByCustomer($customerId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select('`iban`, `bic`, `portable`, `civilite`')
            ->from($this->dbPrefix . 'customer')
            ->where('`id_customer` = :customer_id')
        ;

        $queryBuilder->setParameter('customer_id', $customerId);

        return $queryBuilder->execute()->fetch(PDO::FETCH_COLUMN);
    }

    /**
     * Gets allowed to review status by customer.
     *
     * @param int $customerId
     *
     * @return bool
     */
//    public function getIsAllowedToReviewStatus($customerId)
//    {
//        $queryBuilder = $this->connection->createQueryBuilder();
//
//        $queryBuilder
//            ->select('`is_allowed_for_review`')
//            ->from($this->dbPrefix . 'customer')
//            ->where('`id_customer` = :customer_id')
//        ;
//
//        $queryBuilder->setParameter('customer_id', $customerId);
//
//        return (bool) $queryBuilder->execute()->fetch(PDO::FETCH_COLUMN);
//    }
}
