<?php

use Illuminate\Database\Seeder;
use App\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {	
    	User::create([
        	'name' => '10836023',
            'email'    => '10836023@ntub.edu.tw',
            'password' => Hash::make('10836023'),
            'saved' => json_encode(['meme' => [], 'templates' => []])
        ]);
    }
}
