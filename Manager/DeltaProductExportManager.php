<?php

namespace Pim\Bundle\MagentoConnectorBundle\Manager;

use Doctrine\ORM\EntityRepository;
use PDO;
use Doctrine\ORM\EntityManager;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\CatalogBundle\Repository\ProductRepositoryInterface;
use Pim\Bundle\MagentoConnectorBundle\Builder\TableNameBuilder;

/**
 * Delta product export manager to update and create product export entities
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class DeltaProductExportManager
{
    /** @var boolean */
    protected $productValueDelta;

    /** @var EntityManager */
    protected $entityManager;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var TableNameBuilder */
    protected $tableNameBuilder;

    /** @var string */
    protected $deltaProductParamName;

    /** @var string */
    protected $deltaProductAssoParamName;

    /**
     * @param EntityManager              $entityManager             Entity manager for other entities
     * @param ProductRepositoryInterface $productRepository         Product repository
     * @param boolean                    $productValueDelta         Should we do a delta on product values
     * @param TableNameBuilder           $tableNameBuilder          Table name builder
     * @param string                     $deltaProductParamName     Delta product export entity parameter name
     * @param string                     $deltaProductAssoParamName Delta product association export entity param name
     */
    public function __construct(
        EntityManager $entityManager,
        ProductRepositoryInterface $productRepository,
        $productValueDelta = false,
        TableNameBuilder $tableNameBuilder,
        $deltaProductParamName,
        $deltaProductAssoParamName
    ) {
        $this->entityManager             = $entityManager;
        $this->productRepository         = $productRepository;
        $this->productValueDelta         = $productValueDelta;
        $this->tableNameBuilder          = $tableNameBuilder;
        $this->deltaProductParamName     = $deltaProductParamName;
        $this->deltaProductAssoParamName = $deltaProductAssoParamName;
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
                $this->tableNameBuilder->getTableName($this->deltaProductParamName)
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
                $this->tableNameBuilder->getTableName($this->deltaProductAssoParamName)
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
