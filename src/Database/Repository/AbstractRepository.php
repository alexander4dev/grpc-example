<?php

declare(strict_types=1);

namespace App\Database\Repository;

use Arus\Doctrine\Helper\HydrationInjection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

use function array_map;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function lcfirst;
use function mb_substr;
use function sprintf;
use function str_replace;

abstract class AbstractRepository extends EntityRepository
{
    use HydrationInjection;

    /**
     * @var array
     */
    private $queryAliases = [];

    /**
     * @param array $params
     * @return array
     */
    public function getList(array $params = []): array
    {
        $queryId = $this->generateQueryId(__METHOD__, $params);
        $rootAlias = $this->generateAlias($queryId);
        $qb = $this->createQueryBuilder($rootAlias);

        $selectParams = $params['select'] ?? [];
        $select = [];
        $joins = [];

        foreach ($selectParams as $selectKey => $selectParam) {
            if (is_array($selectParam)) {
                $currentAlias = $this->generateAlias($queryId, $selectKey);
                $qb->leftJoin("$rootAlias.$selectKey", $currentAlias);
                $joins[] = $currentAlias;
                $selectColumns = $selectParam;
            } else {
                $currentAlias = $rootAlias;
                $selectColumns = [$selectParam];
            }

            foreach ($selectColumns as $selectColumn) {
                $select[] = "$currentAlias.$selectColumn";
            }
        }

        if ($select) {
            $qb->select($select);
        }

        $qb->distinct($params['distinct'] ?? false);

        $whereParams = $params['where'] ?? [];

        foreach ($whereParams as $whereKey => $whereParam) {
            if (is_array($whereParam)) {
                $currentAlias = $this->generateAlias($queryId, $whereKey, true);

                if (!in_array($currentAlias, $joins)) {
                    $qb->leftJoin("$rootAlias.$whereKey", $currentAlias);
                    $joins[] = $currentAlias;
                }

                $whereColumns = $whereParam;
            } else {
                $currentAlias = $rootAlias;
                $whereColumns = [$whereKey => $whereParam];
            }

            foreach ($whereColumns as $whereColumn => $whereColumnValue) {
                $whereField = "$currentAlias.$whereColumn";
                $whereParam = str_replace('.', '_', ":eq_$whereField");
                $qb->andWhere($qb->expr()->eq($whereField, $whereParam));
                $qb->setParameter($whereParam, $whereColumnValue);
            }
        }

        $whereIn = $params['whereIn'] ?? [];

        foreach ($whereIn as $whereColumn => $whereColumnValue) {
            $whereField = "$rootAlias.$whereColumn";
            $whereParam = str_replace('.', '_', ":in_$whereField");
            $qb->andWhere($qb->expr()->in($whereField, $whereParam));
            $qb->setParameter($whereParam, $whereColumnValue);
        }

        $indexBy = $params['indexBy'] ?? '';

        if ($indexBy) {
            $qb->indexBy($rootAlias, "$rootAlias.$indexBy");
        }

        $orderBy = $params['orderBy'] ?? [];

        foreach ($orderBy as $orderColumn => $order) {
            $qb->orderBy("$rootAlias.$orderColumn", $order);
        }

        $query = $qb->getQuery();

        $limit = $params['limit'] ?? null;

        if (null !== $limit) {
            $limit = max($limit, 1);
            $query->setMaxResults($limit);
        }

        $page = $params['page'] ?? null;

        if (null !== $page) {
            $offset = $page > 1 ? ($page - 1) * $limit : 0;
            $query->setFirstResult($offset);
        }

        $result = $query->getResult(Query::HYDRATE_ARRAY);
        unset($this->queryAliases[$queryId]);

        return $result;
    }

    /**
     * @param string $prefix
     * @param array $hashParams
     * @return string
     */
    protected function generateQueryId(string $prefix, array $hashParams = []): string
    {
        return md5($prefix . json_encode($hashParams));
    }

    /**
     * @param string $queryId
     * @param string $associationName
     * @param bool $useExisted
     * @return string
     */
    public function generateAlias(string $queryId, string $associationName = null, bool $useExisted = false): string
    {
        $isRootAlias = null === $associationName;

        if ($isRootAlias) {
            $associationName = $this->getClassMetadata()->getTableName();
        }

        $queryAliases = $this->queryAliases[$queryId] ?? [];
        $queryAliasesMerged = $queryAliases ? array_unique(array_merge(...array_values($queryAliases))) : [];
        $aliasCounter = 0;

        do {
            $initials = implode('', array_map(function(string $explode): string {
                return lcfirst(mb_substr($explode, 0, 1));
            }, explode('_', $associationName)));

            $alias = sprintf('%s_%s', $initials, $aliasCounter++);

            if ($useExisted && in_array($alias, $queryAliases[$associationName] ?? [])) {
                break;
            }
        } while (in_array($alias, $queryAliasesMerged));

        $this->queryAliases[$queryId][$associationName][] = $alias;

        return $alias;
    }

    /**
     * @param int|int[] $id
     * @return mixed
     */
    public function deleteById($id)
    {
        $ids = is_array($id) ? $id : [$id];

        if (!$ids) {
            return;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete()
            ->from($this->getClassName(), 'r')
            ->where('r.id IN (:ids)')
            ->setParameters([
                'ids' => $ids,
            ])
        ;

        $executeResult = $qb->getQuery()->execute();

        return $executeResult;
    }
}
