<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServerMonitoringPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Server Monitoring Permissions
        $permissions = [
            'view_other_servers_stats',
            'edit_other_servers_stats',
        ];

        $this->command->info('Server Monitoring Permissions that need to be added:');
        foreach ($permissions as $permission) {
            $this->command->line("- {$permission}");
        }
        
        $this->command->info('\nThese permissions have been added to AuthServiceProvider.');
        $this->command->info('Please add them to your permission groups through the admin interface.');
    }
}
