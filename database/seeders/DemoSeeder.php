<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Enums\VideoRoomStatus;
use App\Events\MeetingCancelled;
use App\Events\MeetingReminder;
use App\Events\SystemAlertBroadcast;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskOverdue;
use App\Events\UserMentionedInChat;
use App\Events\VideoRoomStarted;
use App\Models\ChatMessage;
use App\Models\Hotel;
use App\Models\Meeting;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use App\Models\VideoRoom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Seed a realistic demo scenario with 2 hotels, users, and notifications.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        // --- Hotel 1: The Grand Hotel ---
        $grand = Hotel::create(['name' => 'The Grand Hotel', 'timezone' => 'America/New_York']);

        $grandUsers = $this->createHotelUsers($grand, $password, [
            ['name' => 'Alice Johnson', 'email' => 'alice@grandhotel.com', 'role' => UserRole::Admin],
            ['name' => 'Bob Martinez', 'email' => 'bob@grandhotel.com', 'role' => UserRole::Manager],
            ['name' => 'Carol Davis', 'email' => 'carol@grandhotel.com', 'role' => UserRole::Staff],
            ['name' => 'Dan Wilson', 'email' => 'dan@grandhotel.com', 'role' => UserRole::Staff],
            ['name' => 'Eve Thompson', 'email' => 'eve@grandhotel.com', 'role' => UserRole::Staff],
        ]);

        $this->seedHotelData($grand, $grandUsers);

        // --- Hotel 2: Seaside Resort ---
        $seaside = Hotel::create(['name' => 'Seaside Resort', 'timezone' => 'America/Los_Angeles']);

        $seasideUsers = $this->createHotelUsers($seaside, $password, [
            ['name' => 'Frank Garcia', 'email' => 'frank@seasideresort.com', 'role' => UserRole::Admin],
            ['name' => 'Grace Lee', 'email' => 'grace@seasideresort.com', 'role' => UserRole::Manager],
            ['name' => 'Henry Brown', 'email' => 'henry@seasideresort.com', 'role' => UserRole::Staff],
            ['name' => 'Ivy Chen', 'email' => 'ivy@seasideresort.com', 'role' => UserRole::Staff],
            ['name' => 'Jack Taylor', 'email' => 'jack@seasideresort.com', 'role' => UserRole::Staff],
        ]);

        $this->seedHotelData($seaside, $seasideUsers);

        // Mark some notifications as read for realism
        $this->markSomeAsRead();
    }

    /**
     * @param  array<array{name: string, email: string, role: UserRole}>  $userData
     * @return array<string, User>
     */
    private function createHotelUsers(Hotel $hotel, string $password, array $userData): array
    {
        $users = [];
        foreach ($userData as $data) {
            $users[$data['email']] = User::create([
                'hotel_id' => $hotel->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'password' => $password,
                'email_verified_at' => now(),
            ]);
        }

        return $users;
    }

    /**
     * @param  array<string, User>  $users
     */
    private function seedHotelData(Hotel $hotel, array $users): void
    {
        $userList = array_values($users);
        $admin = $userList[0];
        $manager = $userList[1];
        $staff1 = $userList[2];
        $staff2 = $userList[3];
        $staff3 = $userList[4];

        // --- Tasks ---
        $task1 = Task::create([
            'hotel_id' => $hotel->id,
            'title' => 'Prepare Room 401 for VIP guest',
            'description' => 'Deep clean, fresh flowers, premium amenities kit.',
            'assigned_to' => $staff1->id,
            'created_by' => $manager->id,
            'due_at' => now()->addHours(4),
            'status' => TaskStatus::Pending,
        ]);
        TaskAssigned::dispatch($task1, $manager);

        $task2 = Task::create([
            'hotel_id' => $hotel->id,
            'title' => 'Restock lobby coffee station',
            'description' => 'Coffee beans, cups, stirrers, sugar packets.',
            'assigned_to' => $staff2->id,
            'created_by' => $manager->id,
            'due_at' => now()->subHours(2),
            'status' => TaskStatus::Overdue,
        ]);
        TaskOverdue::dispatch($task2);

        $task3 = Task::create([
            'hotel_id' => $hotel->id,
            'title' => 'Update pool signage for winter hours',
            'description' => 'Replace current signage with winter schedule.',
            'assigned_to' => $staff3->id,
            'created_by' => $admin->id,
            'due_at' => now()->subDay(),
            'status' => TaskStatus::Completed,
        ]);
        TaskCompleted::dispatch($task3, $staff3);

        $task4 = Task::create([
            'hotel_id' => $hotel->id,
            'title' => 'Inspect fire extinguishers on Floor 3',
            'description' => 'Monthly safety inspection.',
            'assigned_to' => $staff1->id,
            'created_by' => $admin->id,
            'due_at' => now()->addDays(2),
            'status' => TaskStatus::Pending,
        ]);
        TaskAssigned::dispatch($task4, $admin);

        // --- Meetings ---
        $meeting1 = Meeting::create([
            'hotel_id' => $hotel->id,
            'title' => 'Weekly Staff Huddle',
            'starts_at' => now()->addMinutes(30),
            'created_by' => $manager->id,
        ]);
        $meeting1->participants()->attach([$manager->id, $staff1->id, $staff2->id, $staff3->id]);
        MeetingReminder::dispatch($meeting1);

        $meeting2 = Meeting::create([
            'hotel_id' => $hotel->id,
            'title' => 'Q1 Budget Review',
            'starts_at' => now()->addDay(),
            'created_by' => $admin->id,
        ]);
        $meeting2->participants()->attach([$admin->id, $manager->id]);
        MeetingCancelled::dispatch($meeting2, $admin);

        // --- Video Room ---
        $videoRoom = VideoRoom::create([
            'hotel_id' => $hotel->id,
            'name' => 'Front Desk Briefing',
            'started_by' => $manager->id,
            'status' => VideoRoomStatus::Active,
        ]);
        VideoRoomStarted::dispatch($videoRoom, collect([$staff1, $staff2]));

        // --- Chat Message with mention ---
        $chatMessage = ChatMessage::create([
            'hotel_id' => $hotel->id,
            'room_id' => fake()->uuid(),
            'sender_id' => $staff2->id,
            'body' => "Hey @{$staff1->name}, can you check on Room 401?",
            'mentioned_user_ids' => [$staff1->id],
        ]);
        UserMentionedInChat::dispatch($chatMessage);

        // --- System Alert ---
        SystemAlertBroadcast::dispatch(
            $hotel,
            'Scheduled Maintenance',
            'The booking system will be offline for maintenance tonight from 2 AM to 4 AM.',
            [UserRole::Admin, UserRole::Manager, UserRole::Staff],
            $admin,
        );
    }

    private function markSomeAsRead(): void
    {
        // Mark roughly 40% of notifications as read for a realistic view
        $notifications = Notification::query()->inRandomOrder()->limit(
            (int) ceil(Notification::count() * 0.4)
        )->get();

        foreach ($notifications as $notification) {
            $notification->update([
                'is_read' => true,
                'read_at' => $notification->created_at->addMinutes(rand(1, 120)),
            ]);
        }
    }
}
