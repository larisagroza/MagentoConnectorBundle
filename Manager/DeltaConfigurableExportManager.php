<?php

namespace Pim\Bundle\MagentoConnectorBundle\Manager;

use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PDO;
use Pim\Bundle\MagentoConnectorBundle\Entity\Repository\GroupRepository;

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

    /**
     * @param EntityManager    $em
     * @param GroupRepository  $groupRepository
     */
    public function __construct(EntityManager $em, GroupRepository $groupRepository)
    {
        $this->em              = $em;
        $this->groupRepository = $groupRepository;
    }

    /**
     * Update configurable delta export
     *
     * @param JobInstance $jobInstance
     * @param string      $identifier
     */
    public function setLastExportDate(JobInstance $jobInstance, $identifier)
    {
        $variantGroup = $this->groupRepository->findOneBy(['code' => $identifier]);
        if ($variantGroup) {
            foreach ($variantGroup->getProducts() as $product) {
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
