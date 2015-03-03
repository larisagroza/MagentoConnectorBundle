<?php

namespace Pim\Bundle\MagentoConnectorBundle\Manager;

use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PDO;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\MagentoConnectorBundle\Entity\Repository\GroupRepository;
use Pim\Bundle\MagentoConnectorBundle\Filter\ExportableProductFilter;

/**
 * Manage DeltaConfigurableExport entities
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class DeltaConfigurableExportManager
{
    /** @var \Doctrine\ORM\EntityManager */
    protected $em;

    /** @var GroupRepository */
    protected $groupRepository;

    /** @var ExportableProductFilter */
    protected $productFilter;

    /**
     * @param EntityManager           $em
     * @param GroupRepository         $groupRepository
     * @param ExportableProductFilter $productFilter
     */
    public function __construct(
        EntityManager $em,
        GroupRepository $groupRepository,
        ExportableProductFilter $productFilter
    ) {
        $this->em              = $em;
        $this->groupRepository = $groupRepository;
        $this->productFilter   = $productFilter;
    }

    /**
     * Update configurable delta export
     *
     * @param Channel     $channel
     * @param JobInstance $jobInstance
     * @param string      $identifier
     */
    public function setLastExportDate(Channel $channel, JobInstance $jobInstance, $identifier)
    {
        $variantGroup = $this->groupRepository->findOneBy(['code' => $identifier]);
        if ($variantGroup) {

            $exportableProducts = $this->productFilter->apply($channel, $variantGroup->getProducts());
            foreach ($exportableProducts as $product) {

                $sql = <<<SQL
                  INSERT INTO pim_magento_delta_configurable_export (product_id, job_instance_id, last_export)
                  VALUES (:product_id, :job_instance_id, :last_export)
                  ON DUPLICATE KEY UPDATE last_export = :last_export
SQL;

                $connection = $this->em->getConnection();
                $query      = $connection->prepare($sql);

                $now           = new \DateTime('now', new \DateTimeZone('UTC'));
                $lastExport    = $now->format('Y-m-d H:i:s');
                $productId     = $product->getId();
                $jobInstanceId = $jobInstance->getId();

                $query->bindParam(':last_export', $lastExport, PDO::PARAM_STR);
                $query->bindParam(':product_id', $productId, PDO::PARAM_INT);
                $query->bindParam(':job_instance_id', $jobInstanceId, PDO::PARAM_INT);
                $query->execute();
            }
        }
    }
}
