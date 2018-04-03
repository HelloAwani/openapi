<?php

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       \DB::table('Permission')->insert([
       		['PermissionID' => 'allow-sync', 'PermissionName' => 'Allow Sync'],
       		['PermissionID' => 'void-transaction', 'PermissionName' => 'Void Transaction']
       	]);
    }
}
