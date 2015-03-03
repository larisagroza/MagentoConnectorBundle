<?php

namespace Pim\Bundle\MagentoConnectorBundle\Manager;

use Doctrine\ORM\EntityRepository;
use PDO;
use Doctrine\ORM\EntityManager;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\CatalogBundle\Repository\ProductRepositoryInterface;

/**
 * Delta product export manager to update and create product export entities
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class DeltaProductExportManager
{
    /** @staticvar string */
    const DELTA_PRODUCT_TABLE = 'pim_magento_delta_product_export';

    /** @staticvar string */
    const DELTA_ASSOCIATION_TABLE = 'pim_magento_delta_product_association_export';

    /** @var boolean */
    protected $productValueDelta;

    /** @var EntityManager */
    protected $entityManager;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /**
     * @param EntityManager                $entityManager           Entity manager for other entities
     * @param ProductRepositoryInterface   $productRepository       Product repository
     * @param boolean                      $productValueDelta       Should we do a delta on product values
     */
    public function __construct(
        EntityManager $entityManager,
        ProductRepositoryInterface $productRepository,
        $productValueDelta = false
    ) {
        $this->entityManager           = $entityManager;
        $this->productRepository       = $productRepository;
        $this->productValueDelta       = $productValueDelta;
    }

    /**
     * Update product export date for the given product
     *
     * @param JobInstance $jobInstance
     * @param string      $identifier
     */
    public function updateProductExport(JobInstance $jobInstance, $identifier)
    {
        $product = $this->productRepository->findByReference((string) $identifier);
        if ($product) {
            $this->updateExport(
                $product,
                $jobInstance,
                static::DELTA_PRODUCT_TABLE
            );
        }
    }

    /**
     * Update product association export date for the given product
     *
     * @param JobInstance $jobInstance
     * @param string      $identifier
     */
    public function updateProductAssociationExport(JobInstance $jobInstance, $identifier)
    {
        $product = $this->productRepository->findByReference((string) $identifier);
        if ($product) {
            $this->updateExport(
                $product,
                $jobInstance,
                static::DELTA_ASSOCIATION_TABLE
            );
        }
    }

    /**
     * Update export date for the given product
     *
     * @param ProductInterface $product
     * @param JobInstance      $jobInstance
     * @param string           $table
     */
    protected function updateExport(
        ProductInterface $product,
        JobInstance $jobInstance,
        $table
    ) {
        $conn = $this->entityManager->getConnection();

        $sql = "
            INSERT INTO $table
            (product_id, job_instance_id, last_export)
            VALUES (:product_id, :job_instance_id, :last_export)
            ON DUPLICATE KEY UPDATE last_export = :last_export
        ";

        $now           = new \DateTime('now', new \DateTimeZone('UTC'));
        $formattedNow  = $now->format('Y-m-d H:i:s');
        $productId     = $product->getId();
        $jobInstanceId = $jobInstance->getId();
        $query         = $conn->prepare($sql);

        $query->bindParam(':last_export', $formattedNow, PDO::PARAM_STR);
        $query->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $query->bindParam(':job_instance_id', $jobInstanceId, PDO::PARAM_INT);
        $query->execute();
    }
}
