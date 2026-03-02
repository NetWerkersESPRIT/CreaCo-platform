<?php

namespace App\Service;

use App\Entity\Users;
use App\Entity\Idea;
use App\Repository\IdeaRepository;
use Doctrine\DBAL\Connection;

class IdeaRecommendationService
{
    public function __construct(
        private IdeaRepository $ideaRepository,
        private Connection $connection
    ) {
    }

    /**
     * @return array<Idea>
     */
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

    /**
     * @return array<Idea>
     */
    public function getTrendingIdeas(int $limit = 5, string $period = 'week'): array
    {
        $days = match ($period) {
            'today' => 1,
            'week' => 7,
            'month' => 30,
            default => 7,
        };

        $trendingIdeaIds = $this->getTrendingIdeaIds($days);

        if (empty($trendingIdeaIds)) {
            return [];
        }

        // Fetch ideas and ensure they are returned in the order of trendingIdeaIds
        $ideas = $this->ideaRepository->findBy(['id' => array_slice($trendingIdeaIds, 0, $limit)]);

        // Sort the results manually to match the trending order
        $orderedIdeas = [];
        foreach ($trendingIdeaIds as $id) {
            foreach ($ideas as $idea) {
                if ($idea->getId() == $id) {
                    $orderedIdeas[] = $idea;
                    break;
                }
            }
            if (count($orderedIdeas) >= $limit) {
                break;
            }
        }

        return $orderedIdeas;
    }

    /**
     * @return array<string, float|int>
     */
    private function getUserCategoryWeights(Users $user): array
    {
        $sql = "SELECT i.category, COUNT(iu.id) as weight
FROM idea i
JOIN idea_usage iu ON iu.idea_id = i.id
WHERE iu.user_id = :userId
GROUP BY i.category";

        /** @var array<string, float|int> $result */
        $result = $this->connection->fetchAllKeyValue($sql, ['userId' => $user->getId()]);
        return $result;
    }

    /**
     * @return array<int>
     */
    private function getTrendingIdeaIds(int $days): array
    {
        // For 'today', we might want to use a different approach if $days is 1
        // but (CURRENT_DATE - INTERVAL 1 DAY) actually covers last 24h roughly if using date comparison.
        // If we want strictly TODAY, we could use mission_date >= CURRENT_DATE

        $sql = "SELECT implement_idea_id FROM mission
WHERE implement_idea_id IS NOT NULL 
  AND mission_date >= (CURRENT_DATE - INTERVAL $days DAY)
GROUP BY implement_idea_id ORDER BY COUNT(*) DESC LIMIT 50";

        /** @var array<int> $result */
        $result = $this->connection->fetchFirstColumn($sql);
        return $result;
    }

    /**
     * @return array<int>
     */
    private function getUsedIdeaIds(Users $user): array
    {
        $sql = "SELECT idea_id FROM idea_usage WHERE user_id = :userId";
        /** @var array<int> $result */
        $result = $this->connection->fetchFirstColumn($sql, ['userId' => $user->getId()]);
        return $result;
    }
}