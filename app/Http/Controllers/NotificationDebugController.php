<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use App\Models\User;
use App\Notifications\TodoAssignedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationDebugController extends Controller
{
    public function testNotificationCreation(Request $request)
    {
        try {
            Log::info('=== NOTIFICATION DEBUG START ===');

            // 1. Check if notifications table exists
            $tableExists = DB::getSchemaBuilder()->hasTable('notifications');
            Log::info('Notifications table exists: '.($tableExists ? 'YES' : 'NO'));

            if (! $tableExists) {
                return response()->json([
                    'error' => 'Notifications table does not exist',
                    'solution' => 'Run: php artisan migrate',
                ], 500);
            }

            // 2. Check database connection
            Log::info('Database connection: '.DB::connection()->getPdo()?->getAttribute(\PDO::ATTR_CONNECTION_STATUS) ?? 'NO CONNECTION');

            // 3. Get a test user
            $testUser = User::first();
            Log::info('Test user found: '.($testUser ? $testUser->name.' (ID: '.$testUser->id.')' : 'NO USER'));

            if (! $testUser) {
                return response()->json([
                    'error' => 'No users found in database',
                ], 500);
            }

            // 4. Create a test todo or get existing
            $testTodo = TodoList::first();
            Log::info('Test todo found: '.($testTodo ? $testTodo->title.' (ID: '.$testTodo->id.')' : 'NO TODO'));

            if (! $testTodo) {
                return response()->json([
                    'error' => 'No todos found in database',
                ], 500);
            }

            // 5. Count notifications before
            $countBefore = DB::table('notifications')->count();
            Log::info('Notifications count before: '.$countBefore);

            // 6. Check queue configuration
            Log::info('Queue connection: '.config('queue.default'));
            Log::info('ShouldQueue interface: '.(class_implements(TodoAssignedNotification::class)['Illuminate\Contracts\Queue\ShouldQueue'] ?? 'NO'));

            // 7. Try to create notification directly (sync)
            Log::info('Creating notification directly...');
            $notification = new TodoAssignedNotification($testTodo, $testUser);

            // Send synchronously for testing
            $testUser->notifyNow($notification);

            // 8. Count notifications after
            $countAfter = DB::table('notifications')->count();
            Log::info('Notifications count after: '.$countAfter);

            // 9. Get the created notification
            $latestNotification = DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $testUser->id)
                ->orderBy('created_at', 'desc')
                ->first();

            Log::info('Latest notification: '.($latestNotification ? 'ID: '.$latestNotification->id.', Type: '.$latestNotification->type : 'NONE'));

            // 10. Check queue jobs table
            $jobsCount = DB::table('jobs')->count();
            Log::info('Queue jobs count: '.$jobsCount);

            // 11. Test queue worker status
            Log::info('Queue configuration checked');

            Log::info('=== NOTIFICATION DEBUG END ===');

            return response()->json([
                'success' => true,
                'debug_data' => [
                    'notifications_table_exists' => $tableExists,
                    'database_connection' => 'OK',
                    'test_user' => $testUser->name.' (ID: '.$testUser->id.')',
                    'test_todo' => $testTodo->title.' (ID: '.$testTodo->id.')',
                    'notifications_count_before' => $countBefore,
                    'notifications_count_after' => $countAfter,
                    'notifications_created' => $countAfter - $countBefore,
                    'latest_notification' => $latestNotification ? [
                        'id' => $latestNotification->id,
                        'type' => $latestNotification->type,
                        'notifiable_id' => $latestNotification->notifiable_id,
                        'created_at' => $latestNotification->created_at,
                    ] : null,
                    'queue_jobs_count' => $jobsCount,
                    'queue_connection' => config('queue.default'),
                    'should_queue' => class_implements(TodoAssignedNotification::class)['Illuminate\Contracts\Queue\ShouldQueue'] ?? false,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Notification debug error: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5),
            ], 500);
        }
    }
}
