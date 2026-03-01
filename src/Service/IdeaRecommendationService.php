<?php

namespace App\Service;

use App\Entity\Users;
use App\Repository\IdeaRepository;
use Doctrine\DBAL\Connection;

class IdeaRecommendationService
{
    public function __construct(
        private IdeaRepository $ideaRepository,
        private Connection $connection
    ) {
    }

    public function getHybridRecommendations(Users $user, int $limit = 10): array
    {
        // 1. Get User's Top Categories (The "Personal" Score)
        $categoryWeights = $this->getUserCategoryWeights($user);

        // 2. Get Trending Ideas (The "Hot" Score - last 7 days)
        $trendingIdeaIds = $this->getTrendingIdeaIds(7);

        // 3. Fetch Ideas excluding already used ones
        $usedIdeaIds = $this->getUsedIdeaIds($user);

        // 4. Query & Rank
// We use a CASE statement in SQL to boost ideas matching the user's top categories
// and ideas that are currently trending.
        return $this->ideaRepository->findRankedIdeas(
            $categoryWeights,
            $trendingIdeaIds,
            $usedIdeaIds,
            $limit
        );
    }

    private function getUserCategoryWeights(Users $user): array
    {
        $sql = "SELECT i.category, COUNT(iu.id) as weight
FROM idea i
JOIN idea_usage iu ON iu.idea_id = i.id
WHERE iu.user_id = :userId
GROUP BY i.category";

        return $this->connection->fetchAllKeyValue($sql, ['userId' => $user->getId()]);
    }

    private function getTrendingIdeaIds(int $days): array
    {
        $sql = "SELECT implement_idea_id FROM mission
WHERE implement_idea_id IS NOT NULL 
  AND mission_date >= (CURRENT_DATE - INTERVAL $days DAY)
  AND mission_date <= (CURRENT_DATE + INTERVAL 1 MONTH)
GROUP BY implement_idea_id ORDER BY COUNT(*) DESC LIMIT 50";

        return $this->connection->fetchFirstColumn($sql);
    }

    private function getUsedIdeaIds(Users $user): array
    {
        $sql = "SELECT idea_id FROM idea_usage WHERE user_id = :userId";
        return $this->connection->fetchFirstColumn($sql, ['userId' => $user->getId()]);
    }
}