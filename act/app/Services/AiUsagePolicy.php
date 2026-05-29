<?php

namespace App\Services;

use App\Models\User;

class AiUsagePolicy
{
    /**
     * Maximum actions per prompt for each user role
     */
    private const ACTION_LIMITS = [
        'root' => 10,
        'admin' => 5,  
        'user' => 3,   
    ];

    /**
     * Get the maximum number of actions allowed per prompt for a user
     *
     * @param User $user
     * @return int
     */
    public static function getActionLimit(User $user): int
    {
        $role = $user->role ?? 'user';
        
        return self::ACTION_LIMITS[$role] ?? self::ACTION_LIMITS['user'];
    }

    /**
     * Get a human-readable message about action limits
     *
     * @param User $user
     * @return string
     */
    public static function getLimitMessage(User $user): string
    {
        $limit = self::getActionLimit($user);
        
        return "You can perform up to {$limit} actions per prompt based on your subscription level.";
    }

    /**
     * Get a warning message for skipped items
     *
     * @param User $user
     * @param int $skippedCount
     * @param array $skippedItems
     * @param string $itemType (e.g., 'project', 'folder', 'survey', 'component')
     * @return string
     */
    public static function getSkippedItemsWarning(User $user, int $skippedCount, array $skippedItems, string $itemType): string
    {
        $limit = self::getActionLimit($user);
        
        if ($skippedCount === 0 || empty($skippedItems)) {
            return '';
        }
        
        // Format skipped items based on type
        $skippedNames = collect($skippedItems)->map(function($item) use ($itemType) {
            // Handle different item structures
            if (is_string($item)) {
                return "'{$item}'";
            }
            
            // Try to extract name from various possible fields
            $name = $item['name'] 
                ?? $item['survey_name'] 
                ?? $item['component_name'] 
                ?? $item['folder_name'] 
                ?? $item['project_name']
                ?? $item['survey_code']
                ?? $item['code']
                ?? 'Unknown';
            
            // For action-based items (manage operations)
            if (isset($item['action'])) {
                return "{$item['action']} '{$name}'";
            }
            
            return "'{$name}'";
        })->join(', ', ' and ');
        
        $itemTypePlural = $itemType . ($skippedCount > 1 ? 's' : '');
        
        return " Note: {$skippedCount} {$itemTypePlural} ({$skippedNames}) could not be processed as they exceed the limit of {$limit} actions per request based on your subscription level.";
    }

    /**
     * Check if user can perform a certain number of actions
     *
     * @param User $user
     * @param int $requestedActions
     * @return bool
     */
    public static function canPerformActions(User $user, int $requestedActions): bool
    {
        return $requestedActions <= self::getActionLimit($user);
    }

    /**
     * Limit an array of items to the user's action limit
     *
     * @param User $user
     * @param array $items
     * @return array ['allowed' => array, 'skipped' => array, 'total' => int, 'limit' => int]
     */
    public static function limitItems(User $user, array $items): array
    {
        $limit = self::getActionLimit($user);
        $total = count($items);
        
        $allowed = array_slice($items, 0, $limit);
        $skipped = array_slice($items, $limit);
        
        return [
            'allowed' => $allowed,
            'skipped' => $skipped,
            'total' => $total,
            'limit' => $limit,
        ];
    }
}