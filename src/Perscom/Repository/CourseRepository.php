<?php

declare(strict_types=1);

namespace Forumify\PerscomPlugin\Perscom\Repository;

use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Repository\AbstractRepository;
use Forumify\PerscomPlugin\Perscom\Entity\Course;

/**
 * @extends AbstractRepository<Course>
 */
class CourseRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return Course::class;
    }

    public function getHighestPosition(): int
    {
        try {
            return $this->createQueryBuilder('e')
                ->select('MAX(e.position)')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
        } catch (\Exception) {
            return 0;
        }
    }

    public function getListQueryBuilder(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')->addOrderBy('c.position', 'ASC');
        $this->addACLToQuery($qb, 'view', alias: 'c');

        return $qb;
    }
}
