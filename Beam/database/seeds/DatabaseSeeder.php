<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
//         $this->call(PropertySeeder::class);
//         $this->call(SegmentSeeder::class);
         $this->call(ArticleSeeder::class);
    }
}
